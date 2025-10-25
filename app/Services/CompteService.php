<?php

namespace App\Services;

use App\Models\Compte;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use Illuminate\Pagination\LengthAwarePaginator;

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
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (isset($filters['statut'])) {
            $query->where('statut', $filters['statut']);
        }
        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        // Sorting
        $sort = $filters['sort'] ?? 'dateCreation';
        $order = $filters['order'] ?? 'desc';
        $allowedSorts = ['dateCreation', 'solde', 'titulaire'];
        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $order);
        }

        // Pagination
        $limit = min($filters['limit'] ?? 10, 100);
        $page = $filters['page'] ?? 1;
        return $query->with('transactions')->paginate($limit, ['*'], 'page', $page);
    }

}