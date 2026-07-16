<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Models live under Core/*/Domain/Models and Modules/*/Domain/Models,
        // not the default App\Models — resolve factories by class basename instead.
        Factory::guessFactoryNamesUsing(
            fn (string $modelName): string => 'Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        // IP-only keying is a deliberate simplification — no per-account
        // compounding at this scale. Known limitation: this repo
        // has no reverse proxy/CDN in front of it today, so `$request->ip()`
        // is trustworthy as-is; if one is added later, `trustProxies()` must
        // be configured in bootstrap/app.php or every request collapses onto
        // the proxy's IP (or becomes spoofable via X-Forwarded-For).
        RateLimiter::for('auth', fn (Request $request): Limit => Limit::perMinute(5)->by($request->ip()));

        // Separate bucket from 'auth' — forgot-password/reset-password are
        // public and free to call, so sharing login's budget would let an
        // attacker exhaust it and lock legitimate users out of login.
        RateLimiter::for(
            'password-reset',
            fn (Request $request): Limit => Limit::perMinute(3)->by($request->ip()),
        );
    }
}
