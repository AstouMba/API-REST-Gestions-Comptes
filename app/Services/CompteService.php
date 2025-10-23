<?php

namespace App\Services;

use App\Models\Compte;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;

class CompteService
{
    public function getAllComptes()
    {
        return Compte::all();
    }

    public function getCompteByNumero($numero)
    {
        $compte = Compte::byNumero($numero)->first();
        if (!$compte) {
            throw new NotFoundException('Compte introuvable');
        }
        return $compte;
    }

    public function createCompte(array $data)
    {
        // Simuler l'utilisateur connecté
        $data['client_id'] = 1; // ID fixe temporaire
        return Compte::create($data);
    }

    public function updateCompte($numero, array $data)
    {
        $compte = $this->getCompteByNumero($numero);
        $compte->update($data);
        return $compte;
    }

    public function deleteCompte($numero)
    {
        $compte = $this->getCompteByNumero($numero);
        $compte->delete();
        return $compte;
    }
}