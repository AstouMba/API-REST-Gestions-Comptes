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
        // Avoid dropping the users table because other tables may have FK constraints
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('login')->unique();
                $table->string('password');
                $table->timestamps();
            });
        } else {
            // If users table already exists, add missing columns safely without dropping the table
            Schema::table('users', function (Blueprint $table) {
                if (! Schema::hasColumn('users', 'login')) {
                    $table->string('login')->unique()->after('id');
                }

                if (! Schema::hasColumn('users', 'password')) {
                    // Some projects already have password; add if missing
                    $table->string('password')->after('login');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // On rollback, do not drop the users table (would cascade to dependent tables).
        // Instead, attempt to remove the columns we added if they exist.
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'login')) {
                    $table->dropColumn('login');
                }

                // Only drop password if it was added by this migration and not used elsewhere.
                if (Schema::hasColumn('users', 'password')) {
                    // Be conservative: do not drop 'password' if there is also a 'remember_token'
                    // which indicates the original users table structure. Skip dropping in that case.
                    if (! Schema::hasColumn('users', 'remember_token')) {
                        $table->dropColumn('password');
                    }
                }
            });
        }
    }
};
