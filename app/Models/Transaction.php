<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    public $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'compte_id',
        'montant',
        'type',
        'description',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
    ];

    public function compte()
    {
        return $this->belongsTo(Compte::class);
    }
}
