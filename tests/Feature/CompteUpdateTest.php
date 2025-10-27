<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Client;
use App\Models\Compte;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class CompteUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_account()
    {
        // Create admin user
        $admin = User::factory()->create(['is_admin' => true]);
        Sanctum::actingAs($admin);

        // Create client and account
        $client = Client::factory()->create();
        $compte = Compte::factory()->create(['client_id' => $client->id, 'statut' => 'actif']);

        // Update data
        $updateData = [
            'titulaire' => 'Updated Titulaire',
            'informationsClient' => [
                'telephone' => '+221771234569',
                'email' => 'updated@example.com'
            ]
        ];

        // Make request
        $response = $this->patchJson('/api/v1/mbow.astou/comptes/' . $compte->id, $updateData);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'id',
                         'numeroCompte',
                         'titulaire',
                         'type',
                         'solde',
                         'devise',
                         'dateCreation',
                         'statut',
                         'motifBlocage',
                         'metadata'
                     ]
                 ]);

        // Verify in database
        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'titulaire' => 'Updated Titulaire',
            'telephone' => '+221771234569',
            'email' => 'updated@example.com'
        ]);
    }

    public function test_client_can_update_own_account()
    {
        // Create client user
        $user = User::factory()->create(['is_admin' => false]);
        $client = Client::factory()->create(['utilisateur_id' => $user->id]);
        Sanctum::actingAs($user);

        // Create account for the client
        $compte = Compte::factory()->create(['client_id' => $client->id, 'statut' => 'actif']);

        // Update data
        $updateData = [
            'informationsClient' => [
                'telephone' => '+221771234570'
            ]
        ];

        // Make request
        $response = $this->patchJson('/api/v1/mbow.astou/comptes/' . $compte->id, $updateData);

        $response->assertStatus(201);

        // Verify in database
        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'telephone' => '+221771234570'
        ]);
    }

    public function test_client_cannot_update_other_clients_account()
    {
        // Create two clients
        $user1 = User::factory()->create(['is_admin' => false]);
        $client1 = Client::factory()->create(['utilisateur_id' => $user1->id]);
        $compte1 = Compte::factory()->create(['client_id' => $client1->id, 'statut' => 'actif']);

        $user2 = User::factory()->create(['is_admin' => false]);
        $client2 = Client::factory()->create(['utilisateur_id' => $user2->id]);
        Sanctum::actingAs($user2);

        // Try to update other client's account
        $updateData = [
            'titulaire' => 'Hacked Titulaire'
        ];

        $response = $this->patchJson('/api/v1/mbow.astou/comptes/' . $compte1->id, $updateData);

        $response->assertStatus(403);
    }

    public function test_update_without_data_returns_422()
    {
        // Create user
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Create account
        $client = Client::factory()->create(['utilisateur_id' => $user->id]);
        $compte = Compte::factory()->create(['client_id' => $client->id, 'statut' => 'actif']);

        // Make request without data
        $response = $this->patchJson('/api/v1/mbow.astou/comptes/' . $compte->id, []);

        $response->assertStatus(422)
                 ->assertJson([
                     'message' => 'Vous devez renseigner au moins un champ pour effectuer une modification.',
                     'errors' => [
                         'general' => [
                             'Vous devez renseigner au moins un champ pour effectuer une modification.'
                         ]
                     ]
                 ]);
    }

    public function test_update_with_invalid_email_returns_error()
    {
        // Create user
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Create account
        $client = Client::factory()->create(['utilisateur_id' => $user->id]);
        $compte = Compte::factory()->create(['client_id' => $client->id]);

        // Update with invalid email
        $updateData = [
            'informationsClient' => [
                'email' => 'invalid-email'
            ]
        ];

        $response = $this->patchJson('/api/v1/mbow.astou/comptes/' . $compte->id, $updateData);

        $response->assertStatus(422);
    }
}