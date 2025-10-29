<?php

namespace Database\Seeders;

use App\Models\Transaction;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pour chaque compte, créer entre 5 et 15 transactions
        \App\Models\Compte::all()->each(function ($compte) {
            // Transactions de dépôt (60% de chances)
            $nbDepots = fake()->numberBetween(3, 10);
            Transaction::factory($nbDepots)->create([
                'compte_id' => $compte->id,
                'type' => 'depot',
                'montant' => fake()->numberBetween(50000, 2000000)
            ]);

            // Transactions de retrait (40% de chances, et montants plus petits)
            $nbRetraits = fake()->numberBetween(2, 5);
            Transaction::factory($nbRetraits)->create([
                'compte_id' => $compte->id,
                'type' => 'retrait',
                'montant' => fake()->numberBetween(10000, 500000)
            ]);
        });
    }
}
