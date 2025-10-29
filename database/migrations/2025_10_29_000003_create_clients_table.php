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
        Schema::create('clients', function (Blueprint $table) {
             $table->uuid('id')->primary();
             $table->uuid('utilisateur_id')->nullable();
             $table->string('titulaire');
             $table->string('email')->unique();
             $table->string('adresse')->nullable();
             $table->string('telephone')->nullable();
             $table->timestamps();

            $table->foreign('utilisateur_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('email');
            $table->index('utilisateur_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
