<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(file_get_contents(database_path('flexclub.sql')));
    }

    public function down(): void
    {
        foreach (['chat_messages', 'chat_threads', 'bookings', 'training_slots', 'membership_requests',
            'plans', 'trainer_gym', 'trainers', 'gyms', 'users'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
