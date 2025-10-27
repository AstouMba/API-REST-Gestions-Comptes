<?php

namespace App\Jobs;

use App\Models\Compte;
use App\Models\Client;
use App\Models\User;
use App\Models\Transaction;
use App\Events\CompteCreated;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class CreateCompteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;

    /**
     * Create a new job instance.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Log the received data for debugging
            Log::info('Job data received', ['data' => $this->data]);

            // Check if data is complete
            if (!isset($this->data['client'])) {
                Log::error('Client data missing in job');
                return;
            }

            // Check if client exists by ID, NCI or telephone
            $client = null;
            if (isset($this->data['client']['id'])) {
                $client = Client::find($this->data['client']['id']);
            }
            if (!$client) {
                $client = Client::where('nci', $this->data['client']['nci'])
                                  ->orWhere('telephone', $this->data['client']['telephone'])
                                  ->first();
            }

            if (!$client) {
                // Create user
                $password = Str::random(8);
                $code = Str::random(6);
                $user = User::create([
                    'id' => Str::uuid(),
                    'login' => $this->data['client']['email'],
                    'password' => Hash::make($password),
                    'code' => $code,
                ]);

                // Create client
                $client = Client::create([
                    'id' => Str::uuid(),
                    'utilisateur_id' => $user->id,
                    'titulaire' => $this->data['client']['titulaire'],
                    'email' => $this->data['client']['email'],
                    'adresse' => $this->data['client']['adresse'],
                    'telephone' => $this->data['client']['telephone'],
                    'nci' => $this->data['client']['nci'],
                ]);
            }

            // Generate unique numeroCompte
            do {
                $numeroCompte = 'CPT' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
            } while (Compte::where('numero', $numeroCompte)->exists());

            // Create account
            $compte = Compte::create([
                'id' => Str::uuid(),
                'client_id' => $client->id,
                'numero' => $numeroCompte,
                'type' => $this->data['type'],
                'statut' => 'actif',
                'devise' => $this->data['devise'],
            ]);

            // Create initial deposit transaction
            Transaction::create([
                'id' => Str::uuid(),
                'compte_id' => $compte->id,
                'montant' => $this->data['soldeInitial'],
                'type' => 'depot',
                'description' => 'Solde initial',
            ]);

            // Fire event for notifications only if new client was created
            if (isset($password)) {
                event(new CompteCreated($compte, $client, $password, $code));
            }

            Log::info('Compte created successfully', ['compte_id' => $compte->id]);

        } catch (\Exception $e) {
            Log::error('Failed to create compte', ['error' => $e->getMessage(), 'data' => $this->data]);
            throw $e;
        }
    }
}
