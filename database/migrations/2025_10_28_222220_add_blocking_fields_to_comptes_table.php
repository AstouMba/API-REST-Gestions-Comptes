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
            if (!Schema::hasColumn('comptes', 'motif_blocage')) {
                $table->string('motif_blocage')->nullable();
            }
            if (!Schema::hasColumn('comptes', 'date_blocage')) {
                $table->timestamp('date_blocage')->nullable();
            }
            if (!Schema::hasColumn('comptes', 'date_deblocage_prevue')) {
                $table->timestamp('date_deblocage_prevue')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comptes', function (Blueprint $table) {
            $columns = ['motif_blocage', 'date_blocage', 'date_deblocage_prevue'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('comptes', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
