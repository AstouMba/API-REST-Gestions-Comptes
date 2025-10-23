<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Compte>
 */
class CompteFactory extends Factory
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
             'client_id' => \App\Models\Client::factory(),
             'numero' => 'ACC' . fake()->unique()->numberBetween(1000, 9999),
             'type' => 'epargne',
             'statut' => 'actif',
         ];
    }
}
