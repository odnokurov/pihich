<?php

namespace App\Services;

use App\Models\Gym;
use App\Models\MembershipRequest;
use App\Models\Plan;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class Memberships
{
    public const TIMEZONE = 'Asia/Tomsk';

    public function submit(User $client, array $data): MembershipRequest
    {
        return DB::transaction(function () use ($client, $data) {
            User::query()->lockForUpdate()->findOrFail($client->id);
            $gym = Gym::query()->where('is_active', true)->lockForUpdate()->find($data['gym_id']);
            $plan = Plan::query()->where('is_active', true)->lockForUpdate()->find($data['plan_id']);

            if (! $gym || ! $plan) {
                throw ValidationException::withMessages(['plan_id' => 'Филиал или тариф больше недоступен.']);
            }

            if (MembershipRequest::where('user_id', $client->id)->where('status', 'pending')->exists()) {
                throw ValidationException::withMessages(['plan_id' => 'У вас уже есть заявка на рассмотрении.']);
            }

            return MembershipRequest::create([
                'user_id' => $client->id,
                'gym_id' => $gym->id,
                'plan_id' => $plan->id,
                ...$this->quote($plan, $data),
            ]);
        }, 3);
    }

    public function quote(Plan $plan, array $data): array
    {
        if ($plan->kind === 'fixed') {
            return $plan->only(['duration_months', 'visits_limit', 'price_kopecks']);
        }

        $options = Validator::make($data, [
            'duration_months' => 'required|integer|between:1,12',
            'visits_per_month' => 'required|integer|between:1,24',
        ])->validate();

        $visits = $options['duration_months'] * $options['visits_per_month'];

        return [...$options, 'visits_limit' => $visits, 'price_kopecks' => $visits * $plan->price_kopecks];
    }

    public function decide(MembershipRequest $application, User $admin, array $data): void
    {
        DB::transaction(function () use ($application, $admin, $data) {
            User::query()->lockForUpdate()->findOrFail($application->user_id);
            $application = MembershipRequest::query()->lockForUpdate()->findOrFail($application->id);

            if ($application->status !== 'pending') {
                throw ValidationException::withMessages(['status' => 'Решение по заявке уже принято.']);
            }

            if ($data['status'] === 'rejected') {
                $application->update([
                    'status' => 'rejected',
                    'rejection_reason' => $data['rejection_reason'],
                    'decided_by' => $admin->id,
                ]);

                return;
            }

            $today = CarbonImmutable::today(self::TIMEZONE);
            $start = empty($data['start_on']) ? $today : CarbonImmutable::parse($data['start_on'], self::TIMEZONE);
            if ($start->lessThan($today)) {
                throw ValidationException::withMessages(['start_on' => 'Укажите сегодняшнюю или будущую дату начала.']);
            }

            $end = $application->duration_months
                ? $start->addMonthsNoOverflow($application->duration_months)
                : $start->addDay();

            if (MembershipRequest::where('user_id', $application->user_id)
                ->where('gym_id', $application->gym_id)->where('status', 'approved')
                ->where('start_on', '<', $end->toDateString())
                ->where('end_on', '>', $start->toDateString())->exists()) {
                throw ValidationException::withMessages(['start_on' => 'В этом филиале на выбранный период уже есть абонемент.']);
            }

            $application->update([
                'status' => 'approved', 'start_on' => $start->toDateString(),
                'end_on' => $end->toDateString(), 'decided_by' => $admin->id,
            ]);
        }, 3);
    }
}
