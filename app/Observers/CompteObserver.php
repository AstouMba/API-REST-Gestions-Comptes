<?php

namespace App\Observers;

use App\Events\CompteCreated;
use App\Models\Compte;
use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Rules\NciRule;
use App\Rules\TelephoneSenegalRule;

class CompteObserver
{
    /**
     * Handle the Compte "creating" event.
     */
    public function creating(Compte $compte): void
    {
        $clientData = $compte->client_data ?? [];
        $data = ['client' => $clientData]; // for login generation

        // Find or create client
        $client = null;
        if (!empty($compte->client_id)) {
            $client = Client::find($compte->client_id);
        }
        if (!$client && !empty($clientData['telephone'])) {
            $client = Client::where('telephone', $clientData['telephone'])->first();
        }
        if (!$client && !empty($clientData['email'])) {
            $client = Client::where('email', $clientData['email'])->first();
        }
        if (!$client && !empty($clientData['nci'])) {
            $client = Client::where('nci', $clientData['nci'])->first();
        }

        $password = null;
        $user = null;

        if (!$client) {
            $password = Str::random(10);
            $login = $clientData['telephone'] ?? $clientData['email'] ?? 'user' . Str::random(6);

            $user = User::create([
                'id' => (string) Str::uuid(),
                'login' => $login,
                'password' => $password,
                'code' => null,
                'is_admin' => false,
            ]);

            $client = Client::create([
                'id' => (string) Str::uuid(),
                'utilisateur_id' => $user->id,
                'titulaire' => $clientData['titulaire'] ?? null,
                'email' => $clientData['email'] ?? null,
                'adresse' => $clientData['adresse'] ?? null,
                'telephone' => $clientData['telephone'] ?? null,
                'nci' => $clientData['nci'] ?? null,
            ]);
        } else {
            $user = $client->utilisateur;
            if (!$user) {
                $password = Str::random(10);
                $login = $client->telephone ?? $client->email ?? 'user' . Str::random(6);
                $user = User::create([
                    'id' => (string) Str::uuid(),
                    'login' => $login,
                    'password' => $password,
                    'code' => null,
                    'is_admin' => false,
                ]);
                $client->utilisateur_id = $user->id;
                $client->save();
            }
        }

        // Validate
        $nciRule = new NciRule();
        if (!empty($clientData['nci'])) {
            $errorMessage = null;
            $nciRule->validate('nci', $clientData['nci'], function($message) use (&$errorMessage) {
                $errorMessage = $message;
            });
            if ($errorMessage !== null) {
                throw new \InvalidArgumentException($errorMessage);
            }
        }

        $telephoneRule = new TelephoneSenegalRule();
        if (!empty($clientData['telephone'])) {
            $errorMessage = null;
            $telephoneRule->validate('telephone', $clientData['telephone'], function($message) use (&$errorMessage) {
                $errorMessage = $message;
            });
            if ($errorMessage !== null) {
                throw new \InvalidArgumentException($errorMessage);
            }
        }

        // Set client_id on compte
        $compte->client_id = $client->id;

        // Generate numero
        if (empty($compte->numero)) {
            $lastCompte = Compte::orderBy('created_at', 'desc')->first();
            $lastNumber = $lastCompte ? (int) substr($lastCompte->numero, 3) : 0;
            $nextNumber = $lastNumber + 1;
            $compte->numero = 'CPT' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
        }

        // Store for later use in created
        $compte->client_instance = $client;
        $compte->user_instance = $user;
        $compte->generated_password = $password;
    }

    /**
     * Handle the Compte "created" event.
     */
    public function created(Compte $compte): void
    {
        // Create initial deposit transaction if soldeInitial is provided
        if (isset($compte->soldeInitial) && is_numeric($compte->soldeInitial)) {
            $compte->depots()->create([
                'id' => (string) Str::uuid(),
                'montant' => $compte->soldeInitial,
                'type' => 'depot',
                'description' => 'Solde initial',
            ]);
        }

        // Generate a verification code and store on user
        $code = (string) random_int(100000, 999999);
        if ($compte->user_instance) {
            $compte->user_instance->code = $code;
            $compte->user_instance->save();
        }

        // Dispatch event to send notifications (mail + sms)
        try {
            event(new CompteCreated($compte, $compte->client_instance, $compte->generated_password, $code));
        } catch (\Exception $e) {
            Log::error('Failed to dispatch CompteCreated event: ' . $e->getMessage());
        }
    }
}
