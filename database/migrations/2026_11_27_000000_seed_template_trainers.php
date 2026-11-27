<?php

use Database\Seeders\TemplateTrainerSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(TemplateTrainerSeeder::class)->run();
    }

    public function down(): void
    {
        // These profiles may already be referenced by training slots or messages.
    }
};
