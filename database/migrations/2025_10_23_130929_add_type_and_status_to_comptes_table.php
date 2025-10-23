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
            $table->enum('type', ['epargne', 'cheque'])->default('epargne');
            $table->enum('statut', ['actif', 'bloque', 'ferme'])->default('actif');
            $table->string('devise')->default('FCFA');
            $table->string('motifBlocage')->nullable();
            $table->timestamp('deleted_at')->nullable();

            $table->index('type');
            $table->index('statut');
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comptes', function (Blueprint $table) {
            $table->dropColumn(['type', 'statut', 'devise', 'motifBlocage', 'deleted_at']);
        });
    }
};
