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
        // Add the `code` column only if it doesn't already exist. This prevents
        // duplicate column errors when reordering or re-running migrations.
        if (!Schema::hasColumn('users', 'code')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('code')->nullable()->after('password');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'code')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('code');
            });
        }
    }
};
