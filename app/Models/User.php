<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens; 

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable; 

    public $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'login',
        'password',
        'code',
        'is_admin',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'password' => 'hashed',
        'is_admin' => 'boolean',
    ];

    /**
     * Surcharger pour utiliser 'login' au lieu de 'email'
     */
    public function findForPassport($login)
    {
        return $this->where('login', $login)->first();
    }

    /**
     * Définir le champ username pour Passport
     */
    public function username()
    {
        return 'login';
    }

    public function admin()
    {
        return $this->hasOne(Admin::class);
    }

    public function client()
    {
        return $this->hasOne(Client::class, 'utilisateur_id');
    }
}