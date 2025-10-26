<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Client extends Model
{
    use HasFactory, Notifiable;

    public $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
         'id',
         'utilisateur_id',
         'titulaire',
         'email',
         'adresse',
         'telephone',
         'nci',
     ];

    public function utilisateur()
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    public function comptes()
    {
        return $this->hasMany(Compte::class);
    }
}
