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
        Schema::connection(config('database.connections.neon.driver') ? 'neon' : null)->create('comptes_archives', function (Blueprint $table) {
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection(config('database.connections.neon.driver') ? 'neon' : null)->dropIfExists('comptes_archives');
    }
};
