<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Billing;

use App\Core\Billing\Domain\Models\Plan;
use App\Core\Tenancy\Domain\Models\Tenant;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TenantRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    public function test_registering_with_a_free_plan_activates_the_tenant_immediately(): void
    {
        $plan = Plan::query()->create($this->planAttributes(['slug' => 'free', 'price_cents' => 0]));

        $response = $this->postJson('/api/v1/tenant-registrations', $this->payload($plan->id));

        $response->assertCreated();
        $response->assertJsonPath('data.tenant.status', 'active');
        $response->assertJsonPath('data.subscription.status', 'active');
        $response->assertJsonPath('data.subscription.expires_at', null);
        $this->assertNotEmpty($response->json('data.token'));

        $tenant = Tenant::query()->where('slug', 'acme-inc')->firstOrFail();
        $this->assertSame('active', $tenant->status->value);
    }

    public function test_registering_with_a_paid_plan_starts_a_trial(): void
    {
        $plan = Plan::query()->create($this->planAttributes(['slug' => 'pro', 'price_cents' => 2900, 'interval' => 'month']));

        $response = $this->postJson('/api/v1/tenant-registrations', $this->payload($plan->id));

        $response->assertCreated();
        $response->assertJsonPath('data.tenant.status', 'trial');
        $response->assertJsonPath('data.subscription.status', 'trialing');
        $this->assertNotNull($response->json('data.subscription.expires_at'));
    }

    public function test_cannot_register_with_an_inactive_plan(): void
    {
        $plan = Plan::query()->create($this->planAttributes(['slug' => 'retired', 'is_active' => false]));

        $this->postJson('/api/v1/tenant-registrations', $this->payload($plan->id))
            ->assertStatus(422);
    }

    public function test_cannot_register_a_duplicate_company_slug(): void
    {
        $plan = Plan::query()->create($this->planAttributes(['slug' => 'free', 'price_cents' => 0]));

        $this->postJson('/api/v1/tenant-registrations', $this->payload($plan->id))->assertCreated();
        $this->postJson('/api/v1/tenant-registrations', $this->payload($plan->id))->assertStatus(422);
    }

    public function test_the_issued_token_authenticates_the_new_admin(): void
    {
        $plan = Plan::query()->create($this->planAttributes(['slug' => 'free', 'price_cents' => 0]));

        $token = $this->postJson('/api/v1/tenant-registrations', $this->payload($plan->id))
            ->assertCreated()
            ->json('data.token');

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/subscription')
            ->assertOk()
            ->assertJsonPath('data.status', 'active');
    }

    private function payload(int $planId): array
    {
        return [
            'company_name' => 'Acme Inc',
            'company_slug' => 'acme-inc',
            'admin_name' => 'Jane Admin',
            'admin_email' => 'jane@acme-inc.test',
            'admin_password' => 'password123',
            'plan_id' => $planId,
        ];
    }

    private function planAttributes(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Plan',
            'slug' => 'plan',
            'price_cents' => 0,
            'interval' => 'free',
            'max_users' => null,
            'features' => [],
            'is_active' => true,
        ], $overrides);
    }
}
