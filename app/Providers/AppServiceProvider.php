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
use Illuminate\Support\Str;
use Laravel\Passport\Passport;

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
        // Restore OAuth keys from environment variables (supports base64 or literal \n)
        $private = env('PASSPORT_PRIVATE_KEY');
        $public = env('PASSPORT_PUBLIC_KEY');

        if ($private) {
            if (! Str::contains($private, '-----BEGIN')) {
                $private = base64_decode($private);
            } else {
                $private = str_replace('\\n', PHP_EOL, $private);
            }
            @file_put_contents(storage_path('oauth-private.key'), $private);
            @chmod(storage_path('oauth-private.key'), 0600);
        }

        if ($public) {
            if (! Str::contains($public, '-----BEGIN')) {
                $public = base64_decode($public);
            } else {
                $public = str_replace('\\n', PHP_EOL, $public);
            }
            @file_put_contents(storage_path('oauth-public.key'), $public);
            @chmod(storage_path('oauth-public.key'), 0644);
        }

        // Tell Passport to load keys from storage (in case we wrote them from env)
        Passport::loadKeysFrom(storage_path());
    }

}
