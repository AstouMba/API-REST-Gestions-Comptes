<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompteArchive extends Model
{
    // Use the neon connection for archives
    protected $connection = 'neon';
    protected $table = 'comptes_archives';

    public $incrementing = false;
    public $keyType = 'string';

    protected $fillable = [
        'id',
        'numero',
        'titulaire',
        'type',
        'solde',
        'statut',
        'devise',
        'dateCreation',
        'dateFermeture',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'dateCreation' => 'datetime',
        'dateFermeture' => 'datetime',
    ];
}
