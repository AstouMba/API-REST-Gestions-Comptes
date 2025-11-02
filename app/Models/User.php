<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
        'is_admin' => 'boolean',
    ];

    protected static function booted()
    {
        static::creating(function ($user) {
            if (!$user->id) {
                $user->id = (string) Str::uuid();
            }
        });
    }

    // Passport login via 'login' instead of email
    public function findForPassport($login)
    {
        return $this->where('login', $login)->first();
    }

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
