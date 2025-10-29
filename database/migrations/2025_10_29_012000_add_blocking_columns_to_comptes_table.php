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
        Schema::table('comptes', function (Blueprint $table) {
            if (! Schema::hasColumn('comptes', 'motif_blocage')) {
                $table->string('motif_blocage')->nullable()->after('devise');
            }
            if (! Schema::hasColumn('comptes', 'date_blocage')) {
                $table->timestamp('date_blocage')->nullable()->after('motif_blocage');
            }
            if (! Schema::hasColumn('comptes', 'date_deblocage_prevue')) {
                $table->timestamp('date_deblocage_prevue')->nullable()->after('date_blocage');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comptes', function (Blueprint $table) {
            if (Schema::hasColumn('comptes', 'date_deblocage_prevue')) {
                $table->dropColumn('date_deblocage_prevue');
            }
            if (Schema::hasColumn('comptes', 'date_blocage')) {
                $table->dropColumn('date_blocage');
            }
            if (Schema::hasColumn('comptes', 'motif_blocage')) {
                $table->dropColumn('motif_blocage');
            }
        });
    }
};
