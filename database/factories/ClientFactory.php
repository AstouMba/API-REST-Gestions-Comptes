<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Liste de prénoms et noms de famille courants au Sénégal
        $prenoms = [
            'Fatou', 'Aïssatou', 'Mariama', 'Khadija', 'Aminata', 'Ndeye', 'Sokhna', 'Astou', 'Mame', 'Oumou',
            'Amadou', 'Mamadou', 'Ibrahima', 'Abdoulaye', 'Ousmane', 'Cheikh', 'Moussa', 'Aliou', 'Pape', 'Serigne'
        ];
        $noms = [
            'Diop', 'Ndiaye', 'Faye', 'Sow', 'Ba', 'Gueye', 'Fall', 'Diallo', 'Sarr', 'Mbaye',
            'Cissé', 'Touré', 'Kane', 'Ndour', 'Samb', 'Wade', 'Mbacké', 'Sy', 'Camara', 'Thiam'
        ];

        // Générer un nom complet aléatoire
        $prenom = $this->faker->randomElement($prenoms);
        $nom = $this->faker->randomElement($noms);
        $titulaire = $prenom . ' ' . $nom;

        // Générer un CNI valide : 13 chiffres, non répétitif, non séquentiel
        do {
            $nci = $this->faker->unique()->numberBetween(1000000000000, 2999999999999); // Commence par 1 ou 2
        } while ($this->isRepetitiveOrSequential($nci));

        return [
             'id' => Str::uuid(),
             'utilisateur_id' => User::factory(),
             'titulaire' => $titulaire,
             'email' => $this->faker->unique()->safeEmail(),
             'adresse' => $this->faker->address(),
             'telephone' => $this->faker->phoneNumber(),
             'nci' => $nci,
         ];
    }

    private function isRepetitiveOrSequential($number): bool
    {
        $str = (string)$number;
        // Vérifier répétitif
        $isRepetitive = true;
        for ($i = 1; $i < 13; $i++) {
            if ($str[$i] !== $str[0]) {
                $isRepetitive = false;
                break;
            }
        }
        if ($isRepetitive) return true;

        // Vérifier séquentiel
        $isSequential = true;
        for ($i = 0; $i < 12; $i++) {
            if ((int)$str[$i] + 1 !== (int)$str[$i + 1]) {
                $isSequential = false;
                break;
            }
        }
        return $isSequential;
    }
}
