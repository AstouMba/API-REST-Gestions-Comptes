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
        if (! Schema::hasTable('comptes')) {
            Schema::create('comptes', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('client_id');
                $table->string('numero')->unique();
                $table->timestamps();

                $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
                $table->index('client_id');
                $table->index('numero');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('comptes')) {
            Schema::dropIfExists('comptes');
        }
    }
};
