<?php

namespace App\Observers;

use App\Models\Compte;

class CompteObserver
{
    /**
     * Handle the Compte "creating" event.
     */
    public function creating(Compte $compte): void
    {
        if (empty($compte->numero)) {
            $lastCompte = Compte::orderBy('created_at', 'desc')->first();
            $lastNumber = $lastCompte ? (int) substr($lastCompte->numero, 3) : 0;
            $nextNumber = $lastNumber + 1;
            $compte->numero = 'CPT' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
        }
    }
}
