<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;
use Str;

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
        $type = fake()->randomElement(['epargne', 'cheque']);
        $statut = 'actif'; // Par défaut

        if ($type === 'epargne') {
            $statut = fake()->randomElement(['actif', 'bloque']);
        }
        // Pour 'cheque', statut reste 'actif'

        $data = [
              'id' =>Str::uuid(),
              'client_id' =>Client::factory(),
              'numero' => 'CPT' . str_pad(fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
              'type' => $type,
              'statut' => $statut,
              'devise' => 'FCFA', // Franc CFA, devise du Sénégal
          ];

        // Ajouter deleted_at pour quelques comptes (environ 10% pour tester les archives)
        if (fake()->boolean(10)) {
            $data['deleted_at'] = fake()->dateTimeBetween('-1 month', 'now');
        }

        return $data;
    }
}
