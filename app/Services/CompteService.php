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

    public function getCompteById(string $id): ?Compte
    {
        // Récupération directe du compte sans vérification d'autorisation pour le moment
        return Compte::withoutGlobalScopes()->find($id);
    }

    public function updateCompte($user = null, $compteId, array $data)
    {
        $compte = Compte::with('client.utilisateur')->find($compteId);
        if (!$compte) {
            throw new \Exception("Compte non trouvé");
        }

        \DB::beginTransaction();
        try {
            // Update titulaire if provided (stored on client)
            if (array_key_exists('titulaire', $data)) {
                $compte->client->titulaire = $data['titulaire'];
                $compte->client->save();
            }

            // Update client informations
            if (!empty($data['informationsClient']) && is_array($data['informationsClient'])) {
                $client = $compte->client;
                $info = $data['informationsClient'];

                if (array_key_exists('telephone', $info) && $info['telephone'] !== null && $info['telephone'] !== '') {
                    $client->telephone = $info['telephone'];
                }
                if (array_key_exists('email', $info) && $info['email'] !== null && $info['email'] !== '') {
                    $client->email = $info['email'];
                }
                if (array_key_exists('nci', $info) && $info['nci'] !== null && $info['nci'] !== '') {
                    $client->nci = $info['nci'];
                }

                $client->save();

                // password on utilisateur (User model)
                if (array_key_exists('password', $info) && $info['password'] !== null && $info['password'] !== '') {
                    $userModel = $client->utilisateur;
                    if ($userModel) {
                        $userModel->password = $info['password'];
                        $userModel->save();
                    }
                }
            }

            \DB::commit();
            return $compte->refresh();
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }
}
