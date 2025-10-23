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

    public function getSoldeAttribute()
    {
        $depots = $this->transactions()->where('type', 'depot')->sum('montant');
        $retraits = $this->transactions()->where('type', 'retrait')->sum('montant');
        $virements = $this->transactions()->where('type', 'virement')->sum('montant');
        return $depots - $retraits - $virements;
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
