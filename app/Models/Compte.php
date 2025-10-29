<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Compte extends Model
{
    use HasFactory, SoftDeletes;

    public $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'client_id',
        'numero',
        'type',
        'statut',
        'devise',
        'motif_blocage',
        'date_blocage',
        'date_deblocage_prevue'
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    /**
     * Appended computed attributes when the model is serialized.
     * We expose `solde` as a computed attribute (not stored in DB).
     */
    protected $appends = ['solde'];


    public function scopeActifs($query)
    {
        return $query->whereNull('deleted_at')
                    ->whereIn('type', ['cheque', 'epargne'])
                    ->where(function($q) {
                        $q->where('statut', 'actif')
                          ->orWhere(function($q) {
                              $q->where('statut', '!=', 'ferme')
                                ->where(function($q) {
                                    $q->whereNull('date_blocage')
                                      ->orWhere('date_blocage', '>', now());
                                });
                          });
                    });
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function transactions()
    {
         return $this->hasMany(Transaction::class);
    }

    public function depots()
    {
         return $this->hasMany(Transaction::class)->where('type', 'depot');
    }

    public function retraits()
    {
         return $this->hasMany(Transaction::class)->where('type', 'retrait');
    }


    public function setNumeroAttribute($value)
    {
        if (!$value) {
            do {
                $value = 'CPT' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
            } while (self::where('numero', $value)->exists());
        }
        $this->attributes['numero'] = $value;
    }

    public function scopeType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeStatut($query, $statut)
    {
        return $query->where('statut', $statut);
    }

    public function scopeSearch($query, $search)
    {
         return $query->where(function ($q) use ($search) {
             $q->where('numero', 'like', '%' . $search . '%')
               ->orWhereHas('client', function ($clientQuery) use ($search) {
                   $clientQuery->where('titulaire', 'like', '%' . $search . '%');
               });
         });
     }

    public function scopeByNumero($query, $numero)
    {
        return $query->where('numero', $numero);
    }

    public function scopeByClient($query, $telephone)
    {
        return $query->whereHas('client', function ($q) use ($telephone) {
            $q->where('telephone', $telephone);
        });
    }

    public function scopeForUser($query, $user)
    {
        if ($user && $user->is_admin) {
            return $query; // Admin sees all
        } elseif ($user) {
            return $query->where('client_id', $user->client->id); // Client sees only their accounts
        } else {
            return $query; // No user, return all
        }
    }

    /**
     * Retourne le solde calculé du compte (dépôts - retraits).
     * Ce champ n'est pas stocké en base ; il est calculé à la volée.
     *
     * La méthode utilise les relations si elles sont préchargées pour éviter
     * des requêtes supplémentaires, sinon elle effectue des agrégations SQL.
     *
     * @return float
     */
    public function getSoldeAttribute(): float
    {
        // Si les relations sont déjà chargées, calculer en mémoire
        if ($this->relationLoaded('depots') && $this->relationLoaded('retraits')) {
            $depots = $this->depots->sum('montant');
            $retraits = $this->retraits->sum('montant');
        } else {
            // Sinon, exécuter des agrégations optimisées en base
            $depots = (float) $this->depots()->sum('montant');
            $retraits = (float) $this->retraits()->sum('montant');
        }

        return (float) ($depots - $retraits);
    }

    /**
     * Scope pour la suppression de compte
     * Permet la suppression sans authentification
     */
    public function scopeCanDelete($query)
    {
        return $query;
    }

}
