<?php

namespace Database\Factories;

use App\Models\Compte;
use Illuminate\Database\Eloquent\Factories\Factory;
use Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Descriptions plus contextuelles pour le Sénégal
        $descriptions = [
            'Dépôt pour achat de tissu',
            'Retrait pour frais de transport',
            'Virement pour paiement de loyer',
            'Dépôt de salaire mensuel',
            'Retrait pour achats alimentaires',
            'Virement familial',
            'Dépôt pour épargne',
            'Retrait pour frais médicaux',
            'Virement pour études',
            'Dépôt de bonus',
        ];

        return [
             'id' => Str::uuid(),
             'compte_id' => Compte::factory(),
             'montant' => fake()->randomFloat(2, 1, 500),
             'type' => fake()->randomElement(['depot', 'retrait', 'virement']),
             'description' => fake()->randomElement($descriptions),
         ];
    }
}
