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

    public function getCompteById($user, $compteId)
    {
        $query = Compte::forUser($user)->where('id', $compteId);

        $compte = $query->first();

        if ($compte) {
            return $compte;
        }

        // If not found locally, simulate serverless search
        // In a real scenario, this would query an external serverless database
        // For now, throw exception if not found
        throw new \App\Exceptions\CompteNotFoundException($compteId);
    }

    public function createCompte(array $data)
    {
        // Log the data for debugging
        \Log::info('Service data received', ['data' => $data]);

        // Dispatch the job for asynchronous creation
        \App\Jobs\CreateCompteJob::dispatch($data);

        // Return a temporary response since it's asynchronous
        return [
            'message' => 'Compte creation request submitted. It will be processed in the background.',
            'status' => 'pending'
        ];
    }

    public function updateCompte($user, $compteId, array $data)
    {
        return \DB::transaction(function () use ($user, $compteId, $data) {
            $compte = $this->getCompteById($user, $compteId);

            // Update titulaire if provided
            if (isset($data['titulaire'])) {
                $compte->client->update(['titulaire' => $data['titulaire']]);
            }

            // Update client informations if provided
            if (isset($data['informationsClient'])) {
                $clientData = [];
                if (isset($data['informationsClient']['telephone'])) {
                    $clientData['telephone'] = $data['informationsClient']['telephone'];
                }
                if (isset($data['informationsClient']['email'])) {
                    $clientData['email'] = $data['informationsClient']['email'];
                }
                if (isset($data['informationsClient']['nci'])) {
                    $clientData['nci'] = $data['informationsClient']['nci'];
                }
                if (!empty($clientData)) {
                    $compte->client->update($clientData);
                }

                // Update password if provided
                if (isset($data['informationsClient']['password'])) {
                    $compte->client->utilisateur->update([
                        'password' => Hash::make($data['informationsClient']['password'])
                    ]);
                }
            }

            return $compte->fresh();
        });
    }

}