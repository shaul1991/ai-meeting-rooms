<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

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
        // Passport token expiration
        Passport::tokensExpireIn(now()->addSeconds((int) config('passport.tokens_expire_in', 3600)));
        Passport::refreshTokensExpireIn(now()->addDays((int) config('passport.refresh_tokens_expire_in', 14)));
        Passport::personalAccessTokensExpireIn(now()->addSeconds((int) config('passport.tokens_expire_in', 3600)));
    }
}
