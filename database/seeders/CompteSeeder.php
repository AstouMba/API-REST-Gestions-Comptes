<?php

namespace Database\Seeders;

use App\Models\Compte;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class CompteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        Compte::truncate();
        Schema::enableForeignKeyConstraints();

        // Créer 30 comptes au total :
        // - 20 comptes courants (chèques)
        // - 10 comptes épargne (dont ~20% seront bloqués par la factory)
        Compte::factory(20)->create(['type' => 'cheque']);
        Compte::factory(10)->create(['type' => 'epargne']);
    }
}
