<?php

namespace App\Services;

use App\Models\Compte;

class CompteService
{
    public function listComptes($user = null, $filters = [])
    {
        $query = Compte::query();

        // Recherche par titulaire ou numéro
        if (!empty($filters['search'])) {
            $query->where('titulaire', 'like', '%'.$filters['search'].'%')
                  ->orWhere('numeroCompte', 'like', '%'.$filters['search'].'%');
        }

        // Filtrer par type de compte
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // Tri
        $sort = $filters['sort'] ?? 'created_at';
        $order = $filters['order'] ?? 'desc';
        $query->orderBy($sort, $order);

        // Pagination
        $limit = $filters['limit'] ?? 10;

        return $query->paginate($limit);
    }

    public function createCompte(array $data)
    {
        return Compte::create($data);
    }

    public function getCompteById($user = null, $compteId)
    {
        $compte = Compte::find($compteId);
        if (!$compte) {
            throw new \Exception("Compte non trouvé");
        }
        return $compte;
    }
}
