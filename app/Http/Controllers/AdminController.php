<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Gym;
use App\Models\MembershipRequest;
use App\Models\Plan;
use App\Models\Trainer;
use App\Models\TrainingSlot;
use App\Models\User;
use App\Services\Memberships;
use App\Services\TrainingBookings;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    public function index()
    {
        $applications = MembershipRequest::where('status', 'pending')
            ->with(['user', 'plan', 'gym'])->oldest('id')->paginate(20);

        return view('admin.applications', compact('applications'));
    }

    public function decide(Request $request, MembershipRequest $application, Memberships $memberships)
    {
        $data = $request->validate([
            'status' => 'required|in:approved,rejected',
            'start_on' => 'exclude_unless:status,approved|nullable|date_format:Y-m-d|after:2000-01-01|before:9998-01-01',
            'rejection_reason' => 'exclude_unless:status,rejected|required|string|max:500',
        ]);
        $memberships->decide($application, $request->user(), $data);

        return back()->with('status', 'Решение сохранено.');
    }

    public function catalog()
    {
        return view('admin.catalog', [
            'gyms' => Gym::orderBy('id')->get(),
            'trainers' => Trainer::with(['user', 'gyms'])->orderBy('id')->get(),
            'plans' => Plan::orderBy('id')->get(),
            'accounts' => User::where('role', 'trainer')->whereDoesntHave('trainer')->orderBy('name')->get(),
        ]);
    }

    public function gym(Request $request, Gym $gym)
    {
        $gym->fill($request->validate([
            'name' => 'required|string|max:120',
            'address' => 'required|string|max:255',
            'description' => 'nullable|string|max:10000',
            'image_path' => $this->imageRule(),
            'is_active' => 'required|boolean',
        ]))->save();

        return back()->with('status', 'Филиал сохранён.');
    }

    public function trainer(Request $request, Trainer $trainer)
    {
        $data = $request->validate([
            ...($trainer->exists ? [] : [
                'user_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', 'trainer'), 'unique:trainers'],
            ]),
            'specialty' => 'required|string|max:120',
            'bio' => 'nullable|string|max:10000',
            'photo_path' => $this->imageRule(),
            'is_active' => 'required|boolean',
            'gyms' => 'nullable|array',
            'gyms.*' => 'required|integer|distinct|exists:gyms,id',
        ]);

        DB::transaction(function () use ($trainer, $data) {
            if ($trainer->exists) {
                $trainer = Trainer::query()->lockForUpdate()->findOrFail($trainer->id);
            } else {
                User::query()->lockForUpdate()->findOrFail($data['user_id']);
                if (Trainer::where('user_id', $data['user_id'])->exists()) {
                    throw ValidationException::withMessages(['user_id' => 'У этой учётной записи уже есть карточка.']);
                }
            }
            $trainer->fill($data)->save();
            $selected = array_map('intval', $data['gyms'] ?? []);
            $links = $trainer->gyms()->pluck('gyms.id')->merge($selected)->unique();
            $trainer->gyms()->syncWithoutDetaching($links->mapWithKeys(
                fn ($id) => [$id => ['is_active' => in_array((int) $id, $selected, true)]],
            )->all());
        }, 3);

        return back()->with('status', 'Карточка тренера сохранена.');
    }

    public function plan(Request $request, Plan $plan)
    {
        $kind = $plan->exists ? $plan->kind : $request->input('kind');
        $request->merge(['kind' => $kind]);
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'kind' => 'required|in:fixed,custom',
            'duration_months' => 'exclude_unless:kind,fixed|required|integer|between:0,12',
            'visits_limit' => 'exclude_unless:kind,fixed|required|integer|between:1,65535',
            'price_kopecks' => ['required', 'integer', 'min:1', 'max:'.($kind === 'custom' ? intdiv(4294967295, 288) : 4294967295)],
            'is_active' => 'required|boolean',
        ]);
        $plan->fill($data)->save();

        return back()->with('status', 'Тариф сохранён.');
    }

    public function slots()
    {
        return view('admin.slots', [
            'slots' => TrainingSlot::with(['trainer.user', 'gym'])->withCount(['bookings as occupied' => fn ($query) => $query->consuming()])
                ->latest('starts_at')->paginate(20),
            'gyms' => Gym::orderBy('name')->get(),
            'trainers' => Trainer::with('user')->orderBy('id')->get(),
        ]);
    }

    public function roster(TrainingSlot $slot)
    {
        $slot->load('gym');

        return view('admin.roster', [
            'slot' => $slot,
            'bookings' => $slot->bookings()->with('user')->orderBy('id')->paginate(30),
        ]);
    }

    public function slot(Request $request, TrainingSlot $slot)
    {
        $data = $request->validate([
            'title' => 'required|string|max:120',
            'gym_id' => 'required|integer|exists:gyms,id',
            'trainer_id' => 'required|integer|exists:trainers,id',
            'starts_at' => 'required|date_format:Y-m-d\TH:i',
            'ends_at' => 'required|date_format:Y-m-d\TH:i|after:starts_at',
            'capacity' => 'required|integer|between:1,65535',
            'is_active' => 'required|boolean',
        ]);
        foreach (['starts_at', 'ends_at'] as $field) {
            $data[$field] = CarbonImmutable::parse($data[$field], Memberships::TIMEZONE)->utc();
            if ($data[$field]->year < 2000 || $data[$field]->year > 2099) {
                throw ValidationException::withMessages([$field => 'Укажите дату с 2000 по 2099 год.']);
            }
        }

        DB::transaction(function () use ($slot, $data) {
            if ($slot->exists) {
                $slot = TrainingSlot::query()->lockForUpdate()->findOrFail($slot->id);
            }
            $slot->fill($data);
            $scheduleChanged = ! $slot->exists || $slot->isDirty(['trainer_id', 'gym_id', 'starts_at', 'ends_at']);

            if ($scheduleChanged || ($slot->isDirty('is_active') && $slot->is_active)) {
                $trainer = Trainer::query()->lockForUpdate()->findOrFail($data['trainer_id']);
                if (! $trainer->is_active || ! $trainer->activeGyms()->whereKey($data['gym_id'])->exists()) {
                    throw ValidationException::withMessages(['trainer_id' => 'Выберите действующего тренера указанного филиала.']);
                }
            }

            if ($slot->exists) {
                $occupied = $slot->bookings()->consuming()->count();
                if ($slot->capacity < $occupied || ($scheduleChanged && $slot->bookings()->exists())) {
                    throw ValidationException::withMessages(['capacity' => 'Для слота с записями сохраните зал, тренера и время. Вместимость должна покрывать все записи.']);
                }
                if (! $slot->is_active && $slot->starts_at->gt(now()) && $slot->bookings()->where('status', 'booked')->exists()) {
                    throw ValidationException::withMessages(['is_active' => 'Будущую тренировку с действующими записями нельзя скрыть.']);
                }
            }

            $slot->save();
        }, 3);

        return back()->with('status', 'Тренировка сохранена.');
    }

    public function attendance(Request $request, TrainingSlot $slot, Booking $booking, TrainingBookings $bookings)
    {
        $data = $request->validate(['status' => 'required|in:attended,missed']);
        $bookings->attendance($slot, $booking, $data['status']);

        return back()->with('status', 'Отметка сохранена.');
    }

    private function imageRule(): array
    {
        return ['nullable', 'string', 'max:255', 'regex:~^(?:images|storage)/(?:[A-Za-z0-9_-]+/)*[A-Za-z0-9_-]+\.(?:png|jpg|jpeg|webp)$~iD'];
    }
}
