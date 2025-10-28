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
        'motifBlocage',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::addGlobalScope('activeAccounts', function ($query) {
            $query->whereNull('deleted_at')
                  ->whereIn('type', ['cheque', 'epargne'])
                  ->where('statut', 'actif');
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
     * Scope pour la suppression de compte
     * Permet la suppression sans authentification
     */
    public function scopeCanDelete($query)
    {
        return $query;
    }

}
