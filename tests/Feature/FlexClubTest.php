<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\ChatThread;
use App\Models\Gym;
use App\Models\MembershipRequest;
use App\Models\Plan;
use App\Models\Trainer;
use App\Models\TrainingSlot;
use App\Models\User;
use App\Services\Memberships;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FlexClubTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $database = getenv('FLEXCLUB_TEST_DATABASE');
        if (! $database) {
            $this->markTestSkipped('Set FLEXCLUB_TEST_DATABASE to a dedicated migrated MySQL database.');
        }

        config([
            'database.default' => 'mysql',
            'database.connections.mysql.url' => null,
            'database.connections.mysql.database' => $database,
        ]);
        DB::beginTransaction();
        $this->beforeApplicationDestroyed(fn () => DB::rollBack());
        $this->travelTo(now('UTC')->setDate(2027, 1, 31)->setTime(5, 0));
    }

    public function test_registration_normalizes_email_and_role_is_assigned_by_server(): void
    {
        $this->get(route('cabinet'))->assertRedirect(route('login'));
        $data = [
            'name' => ' Тестовый клиент ', 'email' => 'CLIENT@example.test', 'role' => 'admin',
            'password' => 'SecretPassword', 'password_confirmation' => 'SecretPassword',
        ];
        $this->post(route('register'), $data)->assertRedirect(route('cabinet'));
        $user = User::where('email', 'client@example.test')->firstOrFail();
        $this->assertSame('client@example.test', $user->email);
        $this->assertSame('Тестовый клиент', $user->name);
        $this->assertSame('client', $user->role);
        $this->get(route('admin'))->assertForbidden();
        $this->post(route('logout'))->assertRedirect(route('home'))->assertSessionHasNoErrors();
        $this->assertGuest();
        $this->post(route('register'), $data)->assertSessionHasErrors('email');
        $this->post(route('login'), ['email' => $user->email, 'password' => 'Incorrect'])->assertSessionHasErrors('email');
        $this->post(route('login'), ['email' => $user->email, 'password' => 'SecretPassword'])->assertRedirect(route('cabinet'));
    }

    public function test_membership_keeps_quoted_price_and_approval_uses_calendar_months(): void
    {
        $client = $this->account('client');
        $admin = $this->account('admin');
        $gym = Gym::firstOrFail();
        $plan = Plan::where('kind', 'custom')->firstOrFail();
        $data = ['gym_id' => $gym->id, 'plan_id' => $plan->id, 'duration_months' => 1, 'visits_per_month' => 4];
        $this->actingAs($client)->post(route('memberships.submit'), $data)->assertSessionHasNoErrors();
        $application = MembershipRequest::where('user_id', $client->id)->firstOrFail();
        $this->assertSame(4, $application->visits_limit);
        $this->assertSame($plan->price_kopecks * 4, $application->price_kopecks);
        $this->post(route('memberships.submit'), $data)->assertSessionHasErrors('plan_id');

        $plan->update(['price_kopecks' => 50000]);
        $decision = route('admin.decide', $application);
        $this->actingAs($admin)->post($decision, ['status' => 'rejected'])->assertSessionHasErrors('rejection_reason');
        $this->post($decision, ['status' => 'approved'])->assertSessionHasNoErrors();
        $application->refresh();
        $this->assertSame('2027-01-31', $application->start_on);
        $this->assertSame('2027-02-28', $application->end_on);
        $this->assertSame(180000, $application->price_kopecks);
        $this->post($decision, ['status' => 'approved'])->assertSessionHasErrors('status');

        $this->actingAs($client)->post(route('memberships.submit'), $data)->assertSessionHasNoErrors();
        $next = MembershipRequest::where('user_id', $client->id)->where('status', 'pending')->firstOrFail();
        $this->actingAs($admin)->post(route('admin.decide', $next), ['status' => 'approved'])
            ->assertSessionHasErrors('start_on');
    }

    public function test_booking_limits_cancellation_rebooking_and_attendance(): void
    {
        $client = $this->account('client');
        $other = $this->account('client');
        $admin = $this->account('admin');
        $gym = Gym::firstOrFail();
        $trainer = $this->trainer($gym);
        $this->approveSingleVisit($client, $admin, $gym);
        $this->approveSingleVisit($other, $admin, $gym);
        $slot = $this->slot($trainer, $gym, 'Первая', 1);
        $second = $this->slot($trainer, $gym, 'Вторая', 2);

        $this->actingAs($client)->post(route('bookings.book', $slot))->assertSessionHasNoErrors();
        $booking = $slot->bookings()->firstOrFail();
        $this->post(route('bookings.book', $second))->assertSessionHasErrors('booking');
        $this->actingAs($other)->post(route('bookings.book', $slot))->assertSessionHasErrors('booking');
        $this->post(route('bookings.cancel', $booking))->assertForbidden();

        $this->actingAs($client)->post(route('bookings.cancel', $booking))->assertSessionHasNoErrors();
        $this->assertSame('cancelled', $booking->refresh()->status);
        $this->post(route('bookings.book', $slot))->assertSessionHasNoErrors();
        $this->assertSame('booked', $booking->refresh()->status);
        $this->assertSame(1, $slot->bookings()->count());

        $this->actingAs($admin)->post(route('admin.attendance', [$slot, $booking]), ['status' => 'attended'])
            ->assertSessionHasErrors('booking');
        $this->travel(90)->minutes();
        $this->post(route('admin.attendance', [$slot, $booking]), ['status' => 'attended'])
            ->assertSessionHasNoErrors();
        $this->assertSame('attended', $booking->refresh()->status);
        $this->assertSame(1, Booking::consuming()->whereKey($booking->id)->count());
        $this->actingAs($client)->get(route('cabinet'))->assertOk()->assertSee('История посещений')->assertSee('Первая');
    }

    public function test_trainer_chat_requires_participation_and_reuses_dialog(): void
    {
        $client = $this->account('client');
        $other = $this->account('client');
        $gym = Gym::firstOrFail();
        $trainer = $this->trainer($gym);
        $open = route('chat.open', $trainer);
        $this->actingAs($client)->post($open)->assertSessionHasNoErrors();
        $thread = ChatThread::where('user_id', $client->id)->firstOrFail();
        $chat = route('chat.show', $thread);

        $trainer->update(['is_active' => false]);
        $this->post($open)->assertRedirect($chat);
        $this->post($chat, ['body' => '   '])->assertSessionHasErrors('body');
        $this->post($chat, ['body' => 'Здравствуйте', 'sender_id' => $other->id])->assertRedirect($chat);
        $this->assertSame($client->id, $thread->messages()->firstOrFail()->sender_id);
        $this->actingAs($trainer->user)->post($chat, ['body' => '<b>Ответ</b>'])->assertRedirect($chat);
        $this->get($chat)->assertOk()->assertSee('&lt;b&gt;Ответ&lt;/b&gt;', false);
        $this->actingAs($other)->get($chat)->assertForbidden();
        $this->post($chat, ['body' => 'Чужое сообщение'])->assertForbidden();
        $this->post($open)->assertSessionHasErrors('trainer');
    }

    public function test_admin_updates_catalog_and_preserves_booked_schedule(): void
    {
        $admin = $this->account('admin');
        $client = $this->account('client');
        $first = Gym::firstOrFail();
        $name = 'Новый зал';
        $this->actingAs($admin)->post(route('admin.gym'), ['name' => $name, 'address' => 'Томск', 'is_active' => 1])
            ->assertSessionHasNoErrors();
        $second = Gym::where('name', $name)->firstOrFail();

        $account = $this->account('trainer');
        $data = ['user_id' => $account->id, 'specialty' => 'Пилатес', 'is_active' => 1, 'gyms' => [$first->id, $second->id]];
        $this->post(route('admin.trainer'), $data)->assertSessionHasNoErrors();
        $trainer = $account->trainer()->firstOrFail();
        $this->get(route('trainers', ['gym' => $first->id]))->assertSee($account->name);
        $this->get(route('trainers', ['gym' => $second->id]))->assertSee($account->name);

        $slotData = [
            'title' => 'Пилатес', 'trainer_id' => $trainer->id, 'gym_id' => $first->id,
            'starts_at' => '2027-01-31T14:00', 'ends_at' => '2027-01-31T15:00',
            'capacity' => 2, 'is_active' => 1,
        ];
        $this->post(route('admin.slot'), $slotData)->assertSessionHasNoErrors();
        $slot = TrainingSlot::where('trainer_id', $trainer->id)->firstOrFail();
        $this->assertSame('2027-01-31 07:00:00', $slot->starts_at->format('Y-m-d H:i:s'));
        $this->approveSingleVisit($client, $admin, $first);
        $this->actingAs($client)->post(route('bookings.book', $slot))->assertSessionHasNoErrors();

        $edit = route('admin.slot', $slot);
        $this->actingAs($admin)->post($edit, array_replace($slotData, ['is_active' => 0]))
            ->assertSessionHasErrors('is_active');
        $this->post($edit, array_replace($slotData, ['gym_id' => $second->id]))->assertSessionHasErrors('capacity');
        $this->post(route('admin.trainer', $trainer), array_replace($data, ['gyms' => [$second->id]]))
            ->assertSessionHasNoErrors();
        $this->assertSame(2, $trainer->gyms()->count());
        $this->get(route('trainers', ['gym' => $first->id]))->assertDontSee($account->name);
        $this->get(route('admin.roster', $slot))->assertOk()->assertSee($client->name);
    }

    private function account(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function trainer(Gym $gym): Trainer
    {
        $trainer = Trainer::create(['user_id' => $this->account('trainer')->id, 'specialty' => 'Силовые тренировки']);
        $trainer->gyms()->attach($gym);

        return $trainer;
    }

    private function approveSingleVisit(User $client, User $admin, Gym $gym): void
    {
        $plan = Plan::where('duration_months', 0)->firstOrFail();
        $service = app(Memberships::class);
        $application = $service->submit($client, ['gym_id' => $gym->id, 'plan_id' => $plan->id]);
        $service->decide($application, $admin, ['status' => 'approved']);
    }

    private function slot(Trainer $trainer, Gym $gym, string $title, int $hours): TrainingSlot
    {
        return TrainingSlot::create([
            'trainer_id' => $trainer->id, 'gym_id' => $gym->id, 'title' => $title,
            'starts_at' => now()->addHours($hours),
            'ends_at' => now()->addHours($hours + 1), 'capacity' => 1,
        ]);
    }
}
