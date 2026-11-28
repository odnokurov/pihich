<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TemplateTrainerSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            if (DB::table('trainers')->exists()) {
                return;
            }

            $gyms = DB::table('gyms')->whereIn('name', ['Алтайская', 'Киевская'])
                ->orderBy('id')->get()->unique('name')->pluck('id', 'name');

            if ($gyms->count() !== 2) {
                return;
            }

            $cards = [
                ['Сергей Шматько', ['Алтайская', 'Киевская']],
                ['Иван Трофимов', ['Алтайская', 'Киевская']],
                ['Елена Швец', ['Алтайская']],
                ['Арсений Колмаков', ['Алтайская']],
                ['Вячеслав Бутусов', ['Киевская']],
                ['Оксана Стариковская', ['Алтайская']],
            ];

            foreach ($cards as $index => [$name, $branches]) {
                $number = $index + 1;
                $user = DB::table('users')->insertGetId([
                    'name' => $name,
                    'email' => 'catalog-'.$number.'-'.Str::lower(Str::random(12)).'@flexclub.test',
                    'password' => Hash::make(Str::random(48)),
                    'role' => 'trainer',
                ]);
                $trainer = DB::table('trainers')->insertGetId([
                    'user_id' => $user,
                    'specialty' => 'Тренажёрный зал',
                    'photo_path' => "images/coaches/coach-$number.png",
                ]);
                DB::table('trainer_gym')->insert(array_map(
                    fn ($branch) => ['trainer_id' => $trainer, 'gym_id' => $gyms[$branch]],
                    $branches,
                ));
            }
        });
    }
}
