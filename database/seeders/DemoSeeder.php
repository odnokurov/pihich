<?php

namespace Database\Seeders;

use App\Models\Gym;
use App\Models\MembershipRequest;
use App\Models\Plan;
use App\Models\Trainer;
use App\Models\TrainingSlot;
use App\Models\User;
use App\Services\Memberships;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            throw new RuntimeException('Учебные данные можно загрузить только при APP_ENV=local.');
        }

        DB::transaction(function () {
            if (User::where('role', '!=', 'trainer')->exists() || TrainingSlot::exists()) {
                throw new RuntimeException('Учебные данные загружаются только в новую базу без клиентов, администраторов и тренировок.');
            }

            $gyms = Gym::orderBy('id')->get();
            $gym = $gyms->firstOrFail();
            foreach ($gyms as $index => $branch) {
                $branch->update(['image_path' => 'images/gyms/gallery-'.($index + 1).'.png']);
            }

            $admin = $this->user('Учебный администратор', 'admin', 'admin');
            $client = $this->user('Учебный клиент', 'client');
            $trainer = $this->createTrainer('Алексей Демин', 'trainer', 'Силовые тренировки', 1, $gyms->modelKeys());
            $this->createTrainer('Марина Скворцова', 'marina', 'Функциональные тренировки', 2, [$gym->id]);

            $today = CarbonImmutable::today(Memberships::TIMEZONE);
            $plan = Plan::where('kind', 'custom')->firstOrFail();
            $application = MembershipRequest::create([
                'user_id' => $client->id, 'gym_id' => $gym->id, 'plan_id' => $plan->id,
                'duration_months' => 3, 'visits_per_month' => 12, 'visits_limit' => 36,
                'price_kopecks' => 36 * $plan->price_kopecks, 'status' => 'approved',
                'start_on' => $today->subMonthNoOverflow()->toDateString(),
                'end_on' => $today->subMonthNoOverflow()->addMonthsNoOverflow(3)->toDateString(),
                'decided_by' => $admin->id,
            ]);

            for ($i = 1; $i <= 30; $i++) {
                $when = $today->addDays($i - 16)->setTime(19, 0)->utc();
                $slot = TrainingSlot::create([
                    'trainer_id' => $trainer->id, 'gym_id' => $gym->id, 'title' => 'Функциональная тренировка',
                    'starts_at' => $when, 'ends_at' => $when->addHour(), 'capacity' => 8,
                ]);
                $status = $when->isPast() ? 'attended' : 'booked';
                $slot->bookings()->create([
                    'user_id' => $client->id, 'membership_id' => $application->id,
                    'status' => $status, 'attended_at' => $status === 'attended' ? $when : null,
                ]);
            }

            TrainingSlot::create([
                'trainer_id' => $trainer->id, 'gym_id' => $gym->id, 'title' => 'Вводная тренировка',
                'starts_at' => $today->addDay()->setTime(16, 0)->utc(),
                'ends_at' => $today->addDay()->setTime(17, 0)->utc(), 'capacity' => 5,
            ]);
        });
    }

    private function user(string $name, string $alias, string $role = 'client'): User
    {
        $user = new User(['name' => $name, 'email' => "$alias@flexclub.test", 'password' => 'FlexclubDemo2026']);
        $user->role = $role;
        $user->save();

        return $user;
    }

    private function createTrainer(string $name, string $alias, string $specialty, int $photo, array $gyms): Trainer
    {
        $user = $this->user($name, $alias, 'trainer');
        $trainer = Trainer::create(['user_id' => $user->id, 'specialty' => $specialty, 'photo_path' => "images/coaches/coach-$photo.png"]);
        $trainer->gyms()->attach($gyms);

        return $trainer;
    }
}
