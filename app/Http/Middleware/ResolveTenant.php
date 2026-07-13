<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Support\ApiResponse;
use App\Core\Tenancy\Application\TenantContext;
use App\Core\Tenancy\Domain\Enums\TenantStatus;
use App\Core\Tenancy\Domain\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class ResolveTenant
{
    public function __construct(
        private readonly TenantContext $context,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->header('X-Tenant-Slug') ?? $this->subdomainFrom($request);

        if ($slug === null) {
            return $next($request);
        }

        $tenant = Tenant::query()->where('slug', $slug)->first();

        if ($tenant === null) {
            return ApiResponse::error('Tenant not found.', 404);
        }

        if ($tenant->status === TenantStatus::Suspended) {
            return ApiResponse::error('This tenant is not currently active.', 403);
        }

        $this->context->set($tenant);

        return $next($request);
    }

    private function subdomainFrom(Request $request): ?string
    {
        $host = $request->getHost();
        $central = parse_url((string) config('app.url'), PHP_URL_HOST) ?? 'localhost';

        if ($host === $central || ! str_ends_with($host, '.'.$central)) {
            return null;
        }

        $subdomain = Str::before($host, '.'.$central);

        return in_array($subdomain, ['www', 'api'], true) ? null : $subdomain;
    }
}
