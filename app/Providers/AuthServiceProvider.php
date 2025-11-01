<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [];

    public function boot(): void
    {
        // Activer le password grant (désactivé par défaut dans les versions récentes de Passport)
        Passport::enablePasswordGrant();

        // Définir les scopes basés sur is_admin
        Passport::tokensCan([
            'admin' => 'Accès administrateur complet',
            'client' => 'Accès client standard',
        ]);

        // Scope par défaut pour les clients
        Passport::setDefaultScope(['client']);

        // Configuration optionnelle des durées de vie
        Passport::tokensExpireIn(now()->addMinutes(60));
        Passport::refreshTokensExpireIn(now()->addDays(30));
    }
}