<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Client;
use App\Models\Compte;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use App\Notifications\CompteCreatedNotification;

class CreateCompteTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_compte_for_existing_client_without_user()
    {
        Notification::fake();

        // Create admin user for authentication
        $admin = User::factory()->admin()->create();

        // Create a client without utilisateur
        $client = Client::factory()->create(['utilisateur_id' => null]);

        $payload = [
            'client_id' => $client->id,
            'type' => 'cheque',
        ];

        $response = $this->actingAs($admin, 'api')->postJson('/api/v1/mbow.astou/comptes', $payload);

        $response->assertStatus(202);

        // Client should now have a linked utilisateur
        $client->refresh();
        $this->assertNotNull($client->utilisateur_id, 'Client should have utilisateur_id after compte creation');

        // There should be a compte linked to the client
        $this->assertDatabaseHas('comptes', [
            'client_id' => $client->id,
            'type' => 'cheque',
        ]);

        // Notification should have been sent to the client
        Notification::assertSentTo($client, CompteCreatedNotification::class);
    }

    public function test_create_compte_for_new_client()
    {
        Notification::fake();

        // Create admin user for authentication
        $admin = User::factory()->admin()->create();

        $payload = [
            'titulaire' => 'Test User',
            'telephone' => '+221771234567',
            'email' => 'testuser@example.com',
            'type' => 'epargne',
        ];

        $response = $this->actingAs($admin, 'api')->postJson('/api/v1/mbow.astou/comptes', $payload);

        $response->assertStatus(202);

        // Client should be created
        $this->assertDatabaseHas('clients', [
            'email' => 'testuser@example.com',
            'telephone' => '+221771234567',
        ]);

        $client = Client::where('email', 'testuser@example.com')->first();
        $this->assertNotNull($client);
        $this->assertNotNull($client->utilisateur_id, 'New client should have a linked utilisateur');

        // Compte should be created
        $this->assertDatabaseHas('comptes', [
            'client_id' => $client->id,
            'type' => 'epargne',
        ]);

        Notification::assertSentTo($client, CompteCreatedNotification::class);
    }
}
