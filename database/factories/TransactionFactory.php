<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

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
        return [
            'id' => \Illuminate\Support\Str::uuid(),
            'compte_id' => \App\Models\Compte::factory(),
            'montant' => fake()->randomFloat(2, 1, 500),
            'type' => fake()->randomElement(['depot', 'retrait', 'virement']),
            'description' => fake()->sentence(),
        ];
    }
}
