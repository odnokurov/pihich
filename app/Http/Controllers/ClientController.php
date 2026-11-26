<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Gym;
use App\Models\MembershipRequest;
use App\Models\TrainingSlot;
use App\Services\Memberships;
use App\Services\TrainingBookings;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $client = $request->user();
        $today = CarbonImmutable::today(Memberships::TIMEZONE);
        $gyms = Gym::where('is_active', true)->orderBy('id')->get();
        $gym = $gyms->firstWhere('id', $request->integer('gym')) ?? $gyms->first();
        $selectedMonth = $request->query('month');
        $month = is_string($selectedMonth) && preg_match('/^20\d{2}-(0[1-9]|1[0-2])$/D', $selectedMonth)
            ? CarbonImmutable::parse("$selectedMonth-01", Memberships::TIMEZONE)
            : $today->startOfMonth();

        $slots = $gym ? TrainingSlot::available()->where('gym_id', $gym->id)
            ->where('starts_at', '>', now())
            ->where('starts_at', '>=', $month->utc())
            ->where('starts_at', '<', $month->addMonth()->utc())
            ->with('trainer.user')->withCount(['bookings as occupied' => fn ($query) => $query->consuming()])
            ->orderBy('starts_at')->get() : collect();

        $applications = MembershipRequest::where('user_id', $client->id)->with(['gym', 'plan'])
            ->withCount(['bookings as used_visits' => fn ($query) => $query->consuming()])
            ->latest('id')->paginate(10, ['*'], 'applications')->withQueryString();

        $bookings = Booking::where('user_id', $client->id)->where('status', '!=', 'attended')
            ->with('slot.gym')->latest('id')->paginate(10, ['*'], 'bookings')->withQueryString();

        $history = Booking::where('user_id', $client->id)->where('status', 'attended')
            ->with('slot.gym')->latest('attended_at')->paginate(10, ['*'], 'history')->withQueryString();

        return view('cabinet', compact('gyms', 'gym', 'month', 'slots', 'applications', 'bookings', 'history'));
    }

    public function submit(Request $request, Memberships $memberships)
    {
        $data = $request->validate([
            'gym_id' => 'required|integer|min:1',
            'plan_id' => 'required|integer|min:1',
            'duration_months' => 'nullable|integer',
            'visits_per_month' => 'nullable|integer',
        ]);

        $memberships->submit($request->user(), $data);

        return redirect()->route('cabinet', ['gym' => $data['gym_id']])->with('status', 'Заявка отправлена.');
    }

    public function book(Request $request, TrainingSlot $slot, TrainingBookings $bookings)
    {
        $bookings->book($slot, $request->user());

        return back()->with('status', 'Тренировка забронирована.');
    }

    public function cancel(Request $request, Booking $booking, TrainingBookings $bookings)
    {
        $bookings->cancel($booking, $request->user());

        return back()->with('status', 'Запись отменена. Тренировка возвращена в абонемент.');
    }
}
