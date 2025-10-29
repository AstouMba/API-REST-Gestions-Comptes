<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Compte;
use App\Models\User;
use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class CompteListingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
        Sanctum::actingAs($this->admin);
    }

    /** @test */
    public function liste_seulement_les_comptes_actifs_et_non_bloques()
    {
        // Compte actif normal
        $compteActif = Compte::factory()->create([
            'type' => 'epargne',
            'statut' => 'actif',
            'date_blocage' => null
        ]);

        // Compte avec blocage futur
        $compteBlocageFutur = Compte::factory()->create([
            'type' => 'epargne',
            'statut' => 'actif',
            'date_blocage' => Carbon::now()->addDays(5),
            'date_deblocage_prevue' => Carbon::now()->addDays(35)
        ]);

        // Compte avec blocage passé (ne devrait pas apparaître)
        $compteBlocagePassé = Compte::factory()->create([
            'type' => 'epargne',
            'statut' => 'actif',
            'date_blocage' => Carbon::now()->subDays(5),
            'date_deblocage_prevue' => Carbon::now()->addDays(25)
        ]);

        // Compte fermé (ne devrait pas apparaître)
        $compteFermé = Compte::factory()->create([
            'type' => 'epargne',
            'statut' => 'ferme'
        ]);

    // Appel de l'API en utilisant le nom de route (prefix dynamique)
    $response = $this->getJson(route('comptes.index'));

        $response->assertStatus(200);

        // Vérifier que seuls les comptes actifs et non bloqués sont présents
        $comptes = $response->json('data');
        
        // Devrait contenir exactement 2 comptes
        $this->assertCount(2, $comptes);

        // Vérifier que les IDs correspondent aux comptes attendus
    $idsRetournés = collect($comptes)->pluck('id')->all();
    $this->assertContains((string) $compteActif->id, $idsRetournés);
    $this->assertContains((string) $compteBlocageFutur->id, $idsRetournés);

        // Vérifier que les comptes non désirés ne sont pas présents
        $this->assertNotContains($compteBlocagePassé->id, $idsRetournés);
        $this->assertNotContains($compteFermé->id, $idsRetournés);
    }

    /** @test */
    public function liste_les_comptes_avec_dates_de_blocage_non_echues()
    {
        // Compte avec date de blocage dans 5 jours
        $compteBlocageFutur = Compte::factory()->create([
            'type' => 'epargne',
            'statut' => 'actif',
            'date_blocage' => Carbon::now()->addDays(5),
            'date_deblocage_prevue' => Carbon::now()->addDays(35)
        ]);

        // Compte avec date de blocage dans le passé
        $compteBlocagePassé = Compte::factory()->create([
            'type' => 'epargne',
            'statut' => 'actif',
            'date_blocage' => Carbon::now()->subDays(1),
            'date_deblocage_prevue' => Carbon::now()->addDays(29)
        ]);

    // Appel de l'API en utilisant le nom de route (prefix dynamique)
    $response = $this->getJson(route('comptes.index'));

        $response->assertStatus(200);
        
        $comptes = $response->json('data');
        
        // Vérifier la présence du compte avec blocage futur
    $this->assertContains((string) $compteBlocageFutur->id, collect($comptes)->pluck('id')->all());

    // Vérifier l'absence du compte avec blocage passé
    $this->assertNotContains((string) $compteBlocagePassé->id, collect($comptes)->pluck('id')->all());
    }
}