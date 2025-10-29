<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Avoid dropping the `users` table here — dropping a table that other
        // tables depend on (clients, admins, etc.) causes migration failures.
        // If the `users` table doesn't exist, create it. If it already exists,
        // skip to avoid destructive changes during a full `migrate:fresh` run.
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('login')->unique();
                $table->string('password');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally left non-destructive. Do not recreate the old users
        // table automatically on rollback — modifications to `users` should be
        // handled in dedicated migrations to avoid FK issues.
    }
};
