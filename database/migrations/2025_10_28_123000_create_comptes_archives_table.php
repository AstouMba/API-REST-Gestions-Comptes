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
        // Create on the 'neon' connection if configured. Skip if not configured or if table already exists.
        $neonConfigured = (bool) config('database.connections.neon');
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
