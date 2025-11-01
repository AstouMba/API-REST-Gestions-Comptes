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
        // Skip entirely if neon connection is not configured (e.g., in testing environment)
        if (config('database.connections.neon') === null || app()->environment('testing')) {
            return;
        }

        // Create on the 'neon' connection if configured. Skip if not configured or if table already exists.
        $neonConfigured = config('database.connections.neon') !== null && is_array(config('database.connections.neon'));
        $connection = $neonConfigured ? 'neon' : null;

        if ($connection) {
            if (! Schema::connection($connection)->hasTable('comptes_archives')) {
                Schema::connection($connection)->create('comptes_archives', function (Blueprint $table) {
                    $table->uuid('id')->primary();
                    $table->string('numero')->unique();
                    $table->string('titulaire');
                    $table->string('type');
                    $table->decimal('solde', 15, 2)->default(0);
                    $table->string('devise')->default('FCFA');
                    $table->string('statut')->default('ferme');
                        // Dates de blocage (le cas échéant)
                        $table->timestamp('date_blocage')->nullable();
                        $table->timestamp('date_deblocage_prevue')->nullable();
                        $table->string('motif_blocage')->nullable();
                    $table->timestamp('dateCreation')->nullable();
                    $table->timestamp('dateFermeture')->nullable();
                    $table->json('metadata')->nullable();
                    $table->timestamps();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $neonConfigured = (bool) config('database.connections.neon');
        $connection = $neonConfigured ? 'neon' : null;
        if ($connection && Schema::connection($connection)->hasTable('comptes_archives')) {
            Schema::connection($connection)->dropIfExists('comptes_archives');
        }
    }
};
