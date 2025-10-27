<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Compte;

class ComptePolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Compte $compte): bool
    {
        // Admin can update any account
        if ($user->is_admin) {
            return true;
        }

        // Client can update only their own accounts
        return $compte->client->utilisateur_id === $user->id;
    }
}
