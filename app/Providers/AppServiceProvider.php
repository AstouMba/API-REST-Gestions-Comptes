<?php

namespace App\Providers;

use App\Services\MailService;
use App\Services\SmsService;
use App\Channels\SmsChannel;
use App\Models\Compte;
use App\Observers\CompteObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Notifications\ChannelManager;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(MailService::class, function ($app) {
            return new MailService();
        });

        $this->app->bind(SmsService::class, function ($app) {
            return new SmsService();
        });
    }

    /**
     * Bootstrap any application services.
     */

    public function boot()
    {
        if (\App::environment('production')) {
            URL::forceScheme('https');
        }

        Compte::observe(CompteObserver::class);

        $this->app->make(ChannelManager::class)->extend('sms', function ($app) {
            return new SmsChannel($app->make(SmsService::class));
        });
    }

}
