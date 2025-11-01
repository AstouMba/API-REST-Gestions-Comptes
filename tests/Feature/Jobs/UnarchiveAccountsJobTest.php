<?php

namespace Tests\Feature\Jobs;

use App\Jobs\UnarchiveAccountsJob;
use App\Models\Admin;
use App\Models\Client;
use App\Models\Compte;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class UnarchiveAccountsJobTest extends TestCase
{
    use RefreshDatabase;

    protected Compte $blockedCompte;
    protected Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        // Skip neon connection setup in tests by setting it to null
        config(['database.connections.neon' => null]);

        // Création d'un utilisateur client
        $user = User::factory()->create([
            'is_admin' => false
        ]);

        // Création d'un client lié à l'utilisateur
        $this->client = Client::factory()->create([
            'utilisateur_id' => $user->id
        ]);

        // Création d'un compte bloqué avec une date de déblocage dans le passé
        $this->blockedCompte = Compte::factory()->create([
            'client_id' => $this->client->id,
            'statut' => 'bloque',
            'motif_blocage' => 'Test blocage',
            'date_blocage' => Carbon::now()->subDays(30),
            'date_deblocage_prevue' => Carbon::now()->subDay() // La date de déblocage est dans le passé
        ]);
    }

    /** @test */
    public function le_job_debloque_les_comptes_dont_la_date_est_passee()
    {
        // Exécution du job
        $job = new UnarchiveAccountsJob();
        $job->handle();

        // Vérification que le compte a été débloqué
        $this->blockedCompte->refresh();

        $this->assertEquals('actif', $this->blockedCompte->statut);
        $this->assertNull($this->blockedCompte->motif_blocage);
        $this->assertNull($this->blockedCompte->date_blocage);
        $this->assertNull($this->blockedCompte->date_deblocage_prevue);
    }

    /** @test */
    public function le_job_ne_debloque_pas_les_comptes_dont_la_date_nest_pas_encore_passee()
    {
        // Mise à jour de la date de déblocage pour qu'elle soit dans le futur
        $this->blockedCompte->update([
            'date_deblocage_prevue' => Carbon::now()->addDays(5)
        ]);

        // Exécution du job
        $job = new UnarchiveAccountsJob();
        $job->handle();

        // Vérification que le compte est toujours bloqué
        $this->blockedCompte->refresh();

        $this->assertEquals('bloque', $this->blockedCompte->statut);
        $this->assertNotNull($this->blockedCompte->motif_blocage);
        $this->assertNotNull($this->blockedCompte->date_blocage);
        $this->assertNotNull($this->blockedCompte->date_deblocage_prevue);
    }
}