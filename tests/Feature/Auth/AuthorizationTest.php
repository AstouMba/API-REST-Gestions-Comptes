<?php

namespace Tests\Feature\Auth;

use App\Models\Admin;
use App\Models\Client;
use App\Models\Compte;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Passport\Client as OAuthClient;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $adminUser;
    protected User $clientUser;
    protected OAuthClient $passwordClient;
    protected string $adminToken;
    protected string $clientToken;
    protected Compte $clientCompte;
    protected Compte $otherCompte;

    protected function setUp(): void
    {
        parent::setUp();

        // Installation de Passport
        $this->artisan('passport:install', ['--no-interaction' => true]);
        $this->passwordClient = OAuthClient::where('password_client', 1)->first();

        // Création d'un admin
        $this->adminUser = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
            'status' => 'active'
        ]);
        Admin::factory()->create(['user_id' => $this->adminUser->id]);

        // Création d'un client
        $this->clientUser = User::factory()->create([
            'email' => 'client@example.com',
            'password' => bcrypt('password123'),
            'status' => 'active'
        ]);
        $client = Client::factory()->create(['user_id' => $this->clientUser->id]);

        // Création des comptes
        $this->clientCompte = Compte::factory()->create([
            'client_id' => $client->id,
            'solde' => 1000
        ]);
        
        $otherClient = Client::factory()->create();
        $this->otherCompte = Compte::factory()->create([
            'client_id' => $otherClient->id,
            'solde' => 2000
        ]);

        // Obtention des tokens
        $this->adminToken = $this->getToken($this->adminUser);
        $this->clientToken = $this->getToken($this->clientUser);
    }

    protected function getToken(User $user): string
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password123'
        ]);

        return $response->json('access_token');
    }

    /** @test */
    public function un_admin_peut_lister_tous_les_comptes()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken
        ])->getJson('/api/comptes');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([
                'id' => $this->clientCompte->id
            ])
            ->assertJsonFragment([
                'id' => $this->otherCompte->id
            ]);
    }

    /** @test */
    public function un_client_ne_peut_voir_que_ses_comptes()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->clientToken
        ])->getJson('/api/comptes');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'id' => $this->clientCompte->id
            ])
            ->assertJsonMissing([
                'id' => $this->otherCompte->id
            ]);
    }

    /** @test */
    public function un_admin_peut_voir_nimporte_quel_compte()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken
        ])->getJson('/api/comptes/' . $this->otherCompte->id);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $this->otherCompte->id
            ]);
    }

    /** @test */
    public function un_client_ne_peut_voir_que_son_propre_compte()
    {
        // Test accès à son propre compte
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->clientToken
        ])->getJson('/api/comptes/' . $this->clientCompte->id);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $this->clientCompte->id
            ]);

        // Test accès à un autre compte
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->clientToken
        ])->getJson('/api/comptes/' . $this->otherCompte->id);

        $response->assertStatus(403);
    }

    /** @test */
    public function un_admin_peut_bloquer_un_compte()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken
        ])->putJson('/api/comptes/' . $this->clientCompte->id . '/block', [
            'motif_blocage' => 'Compte suspect',
            'date_deblocage_prevue' => now()->addDays(30)->toDateTimeString()
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'status' => 'blocked',
                'motif_blocage' => 'Compte suspect'
            ]);
    }

    /** @test */
    public function un_client_ne_peut_pas_bloquer_un_compte()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->clientToken
        ])->putJson('/api/comptes/' . $this->clientCompte->id . '/block', [
            'motif_blocage' => 'Test blocage',
            'date_deblocage_prevue' => now()->addDays(30)->toDateTimeString()
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function un_admin_peut_supprimer_un_compte()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken
        ])->deleteJson('/api/comptes/' . $this->clientCompte->id);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('comptes', ['id' => $this->clientCompte->id]);
    }

    /** @test */
    public function un_client_ne_peut_pas_supprimer_un_compte()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->clientToken
        ])->deleteJson('/api/comptes/' . $this->clientCompte->id);

        $response->assertStatus(403);
        $this->assertDatabaseHas('comptes', ['id' => $this->clientCompte->id]);
    }

    /** @test */
    public function un_admin_peut_effectuer_une_transaction()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken
        ])->postJson('/api/transactions', [
            'compte_source_id' => $this->clientCompte->id,
            'compte_destination_id' => $this->otherCompte->id,
            'montant' => 500,
            'motif' => 'Test transaction'
        ]);

        $response->assertStatus(201);
        
        // Vérification des soldes mis à jour
        $this->assertDatabaseHas('comptes', [
            'id' => $this->clientCompte->id,
            'solde' => 500  // 1000 - 500
        ]);
        $this->assertDatabaseHas('comptes', [
            'id' => $this->otherCompte->id,
            'solde' => 2500  // 2000 + 500
        ]);
    }
}