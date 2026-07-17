<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

final class SetLocaleFromHeader
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->header('X-Locale');
        $availableLocales = config('app.available_locales', [config('app.locale')]);

        if (is_string($locale) && in_array($locale, $availableLocales, true)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
