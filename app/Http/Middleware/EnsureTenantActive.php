<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Support\ApiResponse;
use App\Core\Tenancy\Domain\Enums\TenantStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks product-feature routes for a suspended tenant, based on the
 * AUTHENTICATED user's own tenant_id — not the X-Tenant-Slug header that
 * ResolveTenant reads (the frontend sends a fixed env-configured slug on
 * every request, which doesn't reflect whichever tenant the caller actually
 * belongs to, so that header can't be used for this check).
 *
 * Applied per-route-group in each module's routes/api.php, not globally —
 * deliberately excluded from auth/me, auth/logout, GET+PUT /subscription,
 * and the admin/tenants endpoints, so a suspended tenant's own admin can
 * still see why and self-recover (e.g. switch to the free plan), and a
 * Super Admin can still manage a suspended tenant.
 */
final class EnsureTenantActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->tenant_id === null) {
            return $next($request);
        }

        if ($user->tenant?->status === TenantStatus::Suspended) {
            return ApiResponse::error(
                'Your workspace has been suspended. Contact your administrator or check your subscription.',
                403,
            );
        }

        return $next($request);
    }
}
