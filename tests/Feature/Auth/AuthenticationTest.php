<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Passport\Client;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Client $passwordClient;

    protected function setUp(): void
    {
        parent::setUp();

        // Création d'un client OAuth pour le Password Grant
        $this->artisan('passport:install', ['--no-interaction' => true]);
        
        // On attend que le client soit créé
        $this->passwordClient = Client::where('password_client', 1)->first();
        if (!$this->passwordClient) {
            throw new \RuntimeException('Le client Password Grant n\'a pas été créé correctement.');
        }

        // Création d'un utilisateur de test
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'status' => 'active'
        ]);
    }

    /** @test */
    public function un_utilisateur_peut_se_connecter_avec_des_identifiants_valides()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_in'
            ]);
    }

    /** @test */
    public function la_connexion_echoue_avec_des_identifiants_invalides()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'mauvais_mot_de_passe'
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Ces identifiants sont incorrects.'
            ]);
    }

    /** @test */
    public function un_utilisateur_peut_rafraichir_son_token()
    {
        // D'abord on se connecte pour obtenir un token
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $token = $loginResponse->json('access_token');

        // Ensuite on essaie de rafraîchir le token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/auth/refresh');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_in'
            ]);
    }

    /** @test */
    public function un_utilisateur_peut_se_deconnecter()
    {
        // D'abord on se connecte
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $token = $loginResponse->json('access_token');

        // Ensuite on se déconnecte
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Déconnexion réussie'
            ]);

        // Vérifie que le token n'est plus valide
        $checkResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/user');

        $checkResponse->assertStatus(401);
    }

    /** @test */
    public function un_utilisateur_inactif_ne_peut_pas_se_connecter()
    {
        // On met l'utilisateur en statut inactif
        $this->user->update(['status' => 'inactive']);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Votre compte est désactivé.'
            ]);
    }

    /** @test */
    public function la_validation_des_champs_de_connexion_fonctionne()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'not-an-email',
            'password' => ''
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }
}