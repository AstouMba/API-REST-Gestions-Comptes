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
            // 20% des comptes épargne sont bloqués
            $statut = fake()->boolean(20) ? 'bloque' : 'actif';
        }
        
        // Date de création dans les 2 dernières années
        $dateCreation = fake()->dateTimeBetween('-2 years', 'now');

        $data = [
              'id' =>Str::uuid(),
              'client_id' =>Client::factory(),
              'numero' => 'CPT' . str_pad(fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
              'type' => $type,
              'statut' => $statut,
              'created_at' => $dateCreation,
              'updated_at' => $dateCreation,
              'devise' => 'FCFA', // Franc CFA, devise du Sénégal
          ];

        // Pour les comptes épargne bloqués, ajouter les infos de blocage
        if ($type === 'epargne' && $statut === 'bloque') {
            $dateBlocage = fake()->dateTimeBetween('-1 month', '+1 month');
            $dateDeblocage = fake()->dateTimeBetween($dateBlocage, '+6 months');
            
            $data['motif_blocage'] = fake()->randomElement([
                'Maintenance programmée',
                'Vérification annuelle',
                'Mise à niveau sécurité',
                'Audit interne',
                'Demande client'
            ]);
            $data['date_blocage'] = $dateBlocage;
            $data['date_deblocage_prevue'] = $dateDeblocage;
        }

        // Ajouter deleted_at pour quelques comptes (environ 10% pour tester les archives)
        if (fake()->boolean(10)) {
            $data['deleted_at'] = fake()->dateTimeBetween('-1 month', 'now');
        }

        return $data;
    }
}
