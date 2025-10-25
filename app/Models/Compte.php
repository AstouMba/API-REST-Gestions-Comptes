<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Compte extends Model
{
    use HasFactory;

    public $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
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
                  ->where(function ($q) {
                      $q->where('type', 'cheque')
                        ->orWhere(function ($sub) {
                            $sub->where('type', 'epargne')->where('statut', 'actif');
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
                  $clientQuery->where('nom', 'like', '%' . $search . '%');
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
        if ($user->is_admin) {
            return $query; // Admin sees all
        } else {
            return $query->where('client_id', $user->client->id); // Client sees only their accounts
        }
    }

    public function getTitulaireAttribute()
    {
        return $this->client->nom;
    }

    public function getMetadataAttribute()
    {
        return [
            'derniereModification' => $this->updated_at,
            'version' => 1,
        ];
    }
}
