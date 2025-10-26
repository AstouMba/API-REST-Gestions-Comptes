<?php

namespace App\Services;

use App\Models\Compte;
use App\Models\Client;
use App\Models\User;
use App\Models\Transaction;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CompteService
{
    public function getAllComptes()
    {
        return Compte::all();
    }

    public function getComptesByClient($clientId)
    {
        return Compte::where('client_id', $clientId)->get();
    }
    public function listComptes($user, array $filters = [])
    {
        $query = Compte::forUser($user);

        // Apply filters
        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        // Sorting
        $sort = $filters['sort'] ?? 'created_at';
        $order = $filters['order'] ?? 'desc';
        $allowedSorts = ['created_at', 'solde', 'titulaire'];
        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $order);
        }

        // Pagination
        $limit = min($filters['limit'] ?? 10, 100);
        $page = $filters['page'] ?? 1;
        return $query->with(['transactions', 'client'])->paginate($limit, ['*'], 'page', $page);
    }


    public function getCompteByNumero($user, $numero)
    {
        return Compte::forUser($user)->byNumero($numero)->first();
    }

    public function createCompte(array $data)
    {
        return \DB::transaction(function () use ($data) {
            // Check if client exists by NCI or telephone
            $client = Client::where('nci', $data['client']['nci'])
                              ->orWhere('telephone', $data['client']['telephone'])
                              ->first();

            if (!$client) {
                // Create user
                $password = Str::random(8);
                $code = Str::random(6);
                $user = User::create([
                    'id' => Str::uuid(),
                    'login' => $data['client']['email'],
                    'password' => Hash::make($password),
                    'code' => $code,
                ]);

                // Create client
                $client = Client::create([
                    'id' => Str::uuid(),
                    'utilisateur_id' => $user->id,
                    'titulaire' => $data['client']['titulaire'],
                    'email' => $data['client']['email'],
                    'adresse' => $data['client']['adresse'],
                    'telephone' => $data['client']['telephone'],
                    'nci' => $data['client']['nci'],
                ]);
            }

            // Generate unique numeroCompte
            do {
                $numeroCompte = 'C' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
            } while (Compte::where('numero', $numeroCompte)->exists());

            // Create account
            $compte = Compte::create([
                'id' => Str::uuid(),
                'client_id' => $client->id,
                'numero' => $numeroCompte,
                'type' => $data['type'],
                'statut' => 'actif',
                'devise' => $data['devise'],
            ]);

            // Create initial deposit transaction
            Transaction::create([
                'id' => Str::uuid(),
                'compte_id' => $compte->id,
                'montant' => $data['soldeInitial'],
                'type' => 'depot',
                'description' => 'Solde initial',
            ]);

            // Fire event for notifications only if new client was created
            if (isset($password)) {
                event(new \App\Events\CompteCreated($compte, $client, $password, $code));
            }

            return $compte;
        });
    }

}