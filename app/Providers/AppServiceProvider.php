<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\View\ViewServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Forget the existing binding and bind the ResponseFactory to Laravel's implementation to fix the view method issue
        $this->app->forgetInstance('Illuminate\Contracts\Routing\ResponseFactory');
        $this->app->bind('Illuminate\Contracts\Routing\ResponseFactory', 'Illuminate\Routing\ResponseFactory');
    }
}
