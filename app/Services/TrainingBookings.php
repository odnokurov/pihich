<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\MembershipRequest;
use App\Models\TrainingSlot;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TrainingBookings
{
    public function book(TrainingSlot $slot, User $client): void
    {
        DB::transaction(function () use ($slot, $client) {
            // Follow the same lock order as cancellation: slot, membership, booking.
            $slot = TrainingSlot::query()->lockForUpdate()->findOrFail($slot->id);
            $date = $slot->starts_at->setTimezone(Memberships::TIMEZONE)->toDateString();
            $membership = MembershipRequest::where('user_id', $client->id)->where('gym_id', $slot->gym_id)
                ->covering($date)->lockForUpdate()->first();

            if ($slot->starts_at->lte(now()) || ! TrainingSlot::available()->whereKey($slot->id)->exists()) {
                $this->fail('Эта тренировка недоступна для записи.');
            }

            $previous = $slot->bookings()->where('user_id', $client->id)->first();
            if ($previous && $previous->status !== 'cancelled') {
                $this->fail('Вы уже записаны на эту тренировку.');
            }
            if ($slot->bookings()->consuming()->count() >= $slot->capacity) {
                $this->fail('На тренировке больше нет свободных мест.');
            }
            if (! $membership || $membership->bookings()->consuming()->count() >= $membership->visits_limit) {
                $this->fail('На дату тренировки нужен абонемент этого филиала с доступными тренировками.');
            }

            $slot->bookings()->updateOrCreate(
                ['user_id' => $client->id],
                ['membership_id' => $membership->id, 'status' => 'booked'],
            );
        }, 3);
    }

    public function cancel(Booking $booking, User $client): void
    {
        abort_unless($booking->user_id === $client->id, 403);

        DB::transaction(function () use ($booking) {
            $slot = TrainingSlot::query()->lockForUpdate()->findOrFail($booking->slot_id);
            // A cancelled booking may have been recreated with a different pass.
            $membershipId = $slot->bookings()->whereKey($booking->id)->value('membership_id');
            MembershipRequest::query()->lockForUpdate()->findOrFail($membershipId);
            $booking = Booking::query()->lockForUpdate()->findOrFail($booking->id);

            if ($booking->status !== 'booked' || $slot->starts_at->lte(now())) {
                $this->fail('Отменить запись можно только до начала тренировки.');
            }

            $booking->update(['status' => 'cancelled']);
        }, 3);
    }

    public function attendance(TrainingSlot $slot, Booking $booking, string $status): void
    {
        DB::transaction(function () use ($slot, $booking, $status) {
            $slot = TrainingSlot::query()->lockForUpdate()->findOrFail($slot->id);
            $booking = $slot->bookings()->lockForUpdate()->findOrFail($booking->id);

            if ($slot->starts_at->gt(now()) || $booking->status !== 'booked') {
                $this->fail('Посещение можно отметить после начала тренировки для действующей записи.');
            }

            $booking->update(['status' => $status, 'attended_at' => $status === 'attended' ? now() : null]);
        }, 3);
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['booking' => $message]);
    }
}
