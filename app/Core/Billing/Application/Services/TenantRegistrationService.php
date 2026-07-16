<?php

declare(strict_types=1);

namespace App\Core\Billing\Application\Services;

use App\Core\Auth\Domain\Models\User;
use App\Core\Billing\Application\Contracts\PlanRepositoryInterface;
use App\Core\Billing\Application\DTOs\RegisterTenantData;
use App\Core\Billing\Domain\Models\TenantSubscription;
use App\Core\Tenancy\Application\Contracts\TenantRepositoryInterface;
use App\Core\Tenancy\Application\DTOs\CreateTenantData;
use App\Core\Tenancy\Application\Services\TenantService;
use App\Core\Tenancy\Domain\Enums\TenantStatus;
use App\Core\Tenancy\Domain\Models\Tenant;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Public self-service signup: create a tenant + its first admin + a
 * subscription for the chosen plan, then log the admin straight in.
 */
final class TenantRegistrationService
{
    public function __construct(
        private readonly TenantService $tenantService,
        private readonly TenantRepositoryInterface $tenants,
        private readonly PlanRepositoryInterface $plans,
        private readonly SubscriptionService $subscriptions,
    ) {}

    /**
     * @return array{tenant: Tenant, admin: User, subscription: TenantSubscription, token: string}
     */
    public function register(RegisterTenantData $data): array
    {
        $plan = $this->plans->findById($data->planId);

        if ($plan === null || ! $plan->is_active) {
            throw new ModelNotFoundException('Selected plan is not available.');
        }

        return DB::transaction(function () use ($data, $plan): array {
            $result = $this->tenantService->create(new CreateTenantData(
                name: $data->companyName,
                slug: $data->companySlug,
                adminName: $data->adminName,
                adminEmail: $data->adminEmail,
                adminPassword: $data->adminPassword,
            ));

            $tenant = $result['tenant'];

            if ($plan->price_cents > 0) {
                // No payment gateway yet — a paid plan starts as a trial
                // rather than an active paid tenant.
                $tenant = $this->tenants->update($tenant, ['status' => TenantStatus::Trial]);
            }

            $subscription = $this->subscriptions->createForNewTenant((int) $tenant->id, $plan);

            return [
                'tenant' => $tenant,
                'admin' => $result['admin'],
                'subscription' => $subscription,
                'token' => $result['admin']->createToken('api')->plainTextToken,
            ];
        });
    }
}
