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
        Schema::connection('neon')->create('comptes_archives', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('compte_id');
            $table->string('numero_compte');
            $table->string('type');
            $table->string('statut');
            $table->decimal('solde', 15, 2);
            $table->string('devise');
            $table->string('motif_blocage')->nullable();
            $table->timestamp('date_blocage')->nullable();
            $table->timestamp('date_deblocage_prevue')->nullable();
            $table->timestamp('date_archivage');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comptes_archives');
    }
};
