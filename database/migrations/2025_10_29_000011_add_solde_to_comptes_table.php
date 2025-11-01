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
<<<<<<< HEAD:database/migrations/2025_10_29_000011_add_solde_to_comptes_table.php
            $table->decimal('solde', 15, 2)->after('statut')->default(0);
=======
            if (!Schema::hasColumn('comptes', 'motif_blocage')) {
                $table->string('motif_blocage')->nullable();
            }
            if (!Schema::hasColumn('comptes', 'date_blocage')) {
                $table->timestamp('date_blocage')->nullable();
            }
            if (!Schema::hasColumn('comptes', 'date_deblocage_prevue')) {
                $table->timestamp('date_deblocage_prevue')->nullable();
            }
>>>>>>> production:database/migrations/2025_10_28_222220_add_blocking_fields_to_comptes_table.php
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comptes', function (Blueprint $table) {
<<<<<<< HEAD:database/migrations/2025_10_29_000011_add_solde_to_comptes_table.php
            $table->dropColumn('solde');
=======
            $columns = ['motif_blocage', 'date_blocage', 'date_deblocage_prevue'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('comptes', $column)) {
                    $table->dropColumn($column);
                }
            }
>>>>>>> production:database/migrations/2025_10_28_222220_add_blocking_fields_to_comptes_table.php
        });
    }
};
