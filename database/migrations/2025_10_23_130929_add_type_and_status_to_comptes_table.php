<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('comptes')) {
            Schema::table('comptes', function (Blueprint $table) {
                if (! Schema::hasColumn('comptes', 'type')) {
                    $table->enum('type', ['epargne', 'cheque'])->default('epargne');
                }
                if (! Schema::hasColumn('comptes', 'statut')) {
                    $table->enum('statut', ['actif', 'bloque', 'ferme'])->default('actif');
                }
                if (! Schema::hasColumn('comptes', 'devise')) {
                    $table->string('devise')->default('FCFA');
                }
                if (! Schema::hasColumn('comptes', 'motifBlocage')) {
                    $table->string('motifBlocage')->nullable();
                }
                if (! Schema::hasColumn('comptes', 'deleted_at')) {
                    $table->timestamp('deleted_at')->nullable();
                }

                // Index creation handled after the table alteration to avoid duplicate-index errors.
            });

            // Create indexes only if they do not already exist (Postgres specific check)
            try {
                $exists = DB::selectOne("SELECT to_regclass('public.comptes_type_index') as name");
                if (empty($exists) || $exists->name === null) {
                    DB::statement('CREATE INDEX comptes_type_index ON comptes("type")');
                }
            } catch (\Throwable $e) {
                // ignore: platforms without to_regclass or other issues
            }

            try {
                $exists = DB::selectOne("SELECT to_regclass('public.comptes_statut_index') as name");
                if (empty($exists) || $exists->name === null) {
                    DB::statement('CREATE INDEX comptes_statut_index ON comptes("statut")');
                }
            } catch (\Throwable $e) {
                // ignore
            }

            try {
                $exists = DB::selectOne("SELECT to_regclass('public.comptes_deleted_at_index') as name");
                if (empty($exists) || $exists->name === null) {
                    DB::statement('CREATE INDEX comptes_deleted_at_index ON comptes("deleted_at")');
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('comptes')) {
            Schema::table('comptes', function (Blueprint $table) {
                $drop = [];
                foreach (['type', 'statut', 'devise', 'motifBlocage', 'deleted_at'] as $col) {
                    if (Schema::hasColumn('comptes', $col)) {
                        $drop[] = $col;
                    }
                }

                if (! empty($drop)) {
                    $table->dropColumn($drop);
                }
            });
        }
    }
};
