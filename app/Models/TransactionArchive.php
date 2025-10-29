<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionArchive extends Model
{
    protected $table = 'transactions_archives';

    protected $fillable = [
        'transaction_id',
        'compte_id',
        'type',
        'montant',
        'date_transaction',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'date_transaction' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function compte()
    {
        return $this->belongsTo(CompteArchive::class, 'compte_id');
    }
}