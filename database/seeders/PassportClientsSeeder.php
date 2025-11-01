<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Laravel\Passport\ClientRepository;
use Illuminate\Support\Facades\Storage;

class PassportClientsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $repo = new ClientRepository();

        // Create or get a Password Grant client
        $passwordClient = $repo->createPasswordGrantClient(null, 'API Password Grant Client', 'http://localhost');

        // Create or get a Personal Access client
        $personalClient = $repo->createPersonalAccessClient(null, 'API Personal Access Client', 'http://localhost');

        $payload = [
            'password_client' => [
                'id' => $passwordClient->id,
                'secret' => $passwordClient->secret ?? null,
            ],
            'personal_client' => [
                'id' => $personalClient->id,
                'secret' => $personalClient->secret ?? null,
            ],
        ];

        // Save the generated clients details for developer convenience
        if (!Storage::exists('passport_clients')) {
            Storage::put('passport_clients', json_encode($payload, JSON_PRETTY_PRINT));
        } else {
            Storage::put('passport_clients', json_encode($payload, JSON_PRETTY_PRINT));
        }

        $this->command->info('Passport clients created. Details saved to storage/app/passport_clients');
    }
}
