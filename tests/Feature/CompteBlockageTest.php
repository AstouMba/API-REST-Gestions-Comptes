<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Compte;
use Carbon\Carbon;
use App\Services\CompteBlockageService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CompteBlockageTest extends TestCase
{
    use RefreshDatabase;

    private CompteBlockageService $blockageService;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->blockageService = new CompteBlockageService();
    }

    /** @test */
    public function peut_planifier_blocage_compte_epargne()
    {
        $compte = Compte::factory()->create([
            'type' => 'epargne',
            'statut' => 'actif'
        ]);

        $this->blockageService->planifierBlocage($compte, 'Maintenance', 3, 'mois');

        $this->assertNotNull($compte->fresh()->date_blocage);
        $this->assertNotNull($compte->fresh()->date_deblocage_prevue);
        $this->assertEquals('Maintenance', $compte->fresh()->motif_blocage);
    }

    /** @test */
    public function ne_peut_pas_planifier_blocage_compte_courant()
    {
        $compte = Compte::factory()->create([
            'type' => 'courant',
            'statut' => 'actif'
        ]);

        $this->expectException(\InvalidArgumentException::class);

        $this->blockageService->planifierBlocage($compte, 'Maintenance', 3, 'mois');
    }

    /** @test */
    public function peut_bloquer_comptes_echus()
    {
        // Créer un compte avec une date de blocage dans le passé
        $compte = Compte::factory()->create([
            'type' => 'epargne',
            'statut' => 'actif',
            'date_blocage' => Carbon::now()->subDay(),
        ]);

        $this->blockageService->bloquerComptesEchus();

        $this->assertEquals('bloque', $compte->fresh()->statut);
    }

    /** @test */
    public function peut_debloquer_compte_bloque()
    {
        $compte = Compte::factory()->create([
            'type' => 'epargne',
            'statut' => 'bloque',
            'motif_blocage' => 'Maintenance',
            'date_blocage' => Carbon::now(),
            'date_deblocage_prevue' => Carbon::now()->addMonths(6)
        ]);

        $this->blockageService->debloquerCompte($compte);

        $compte = $compte->fresh();
        $this->assertEquals('actif', $compte->statut);
        $this->assertNull($compte->motif_blocage);
        $this->assertNull($compte->date_blocage);
        $this->assertNull($compte->date_deblocage_prevue);
    }

    /** @test */
    public function ne_peut_pas_debloquer_compte_non_bloque()
    {
        $compte = Compte::factory()->create([
            'type' => 'epargne',
            'statut' => 'actif'
        ]);

        $this->expectException(\InvalidArgumentException::class);

        $this->blockageService->debloquerCompte($compte);
    }

    /** @test */
    public function verifie_eligibilite_blocage()
    {
        $compteEligible = Compte::factory()->create([
            'type' => 'epargne',
            'statut' => 'actif',
            'date_blocage' => null
        ]);

        $compteNonEligible = Compte::factory()->create([
            'type' => 'courant',
            'statut' => 'actif'
        ]);

        $this->assertTrue($this->blockageService->peutEtreProgrammePourBlocage($compteEligible));
        $this->assertFalse($this->blockageService->peutEtreProgrammePourBlocage($compteNonEligible));
    }

    /** @test */
    public function verifie_eligibilite_deblocage()
    {
        $compteBloque = Compte::factory()->create([
            'type' => 'epargne',
            'statut' => 'bloque'
        ]);

        $compteActif = Compte::factory()->create([
            'type' => 'epargne',
            'statut' => 'actif'
        ]);

        $this->assertTrue($this->blockageService->peutEtreDebloque($compteBloque));
        $this->assertFalse($this->blockageService->peutEtreDebloque($compteActif));
    }
}