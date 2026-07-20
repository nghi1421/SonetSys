<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Settings;

use App\Core\Auth\Domain\Models\User;
use App\Core\Settings\Domain\Models\SystemSetting;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class SystemSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    public function test_default_settings_are_seeded_from_env(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        // phpunit.xml pins CACHE_STORE=array, which isn't a valid
        // CacheDriver case, so the migration falls back to 'database'.
        $this->getJson('/api/v1/settings')
            ->assertOk()
            ->assertJsonPath('data.cache_driver', 'database')
            ->assertJsonPath('data.has_redis_password', false)
            ->assertJsonPath('data.has_mail_password', false)
            ->assertJsonPath('data.max_upload_size_kb', 20480);
    }

    public function test_non_manager_cannot_view_or_update_settings(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/settings')->assertForbidden();
        $this->putJson('/api/v1/settings', $this->validPayload())->assertForbidden();
    }

    public function test_manager_can_update_settings_and_secrets_are_never_exposed(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $response = $this->putJson('/api/v1/settings', $this->validPayload([
            'redis_password' => 'super-secret-redis',
            'mail_password' => 'super-secret-mail',
        ]));

        $response->assertOk();
        $response->assertJsonPath('data.cache_driver', 'redis');
        $response->assertJsonPath('data.redis_host', 'cache.internal');
        $response->assertJsonPath('data.has_redis_password', true);
        $response->assertJsonPath('data.has_mail_password', true);
        $response->assertJsonMissingPath('data.redis_password');
        $response->assertJsonMissingPath('data.mail_password');

        $raw = json_encode($response->json());
        $this->assertStringNotContainsString('super-secret-redis', (string) $raw);
        $this->assertStringNotContainsString('super-secret-mail', (string) $raw);
    }

    public function test_updating_settings_without_a_password_keeps_the_previously_saved_one(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->putJson('/api/v1/settings', $this->validPayload([
            'redis_password' => 'super-secret-redis',
        ]))->assertOk();

        $storedPassword = SystemSetting::query()->firstOrFail()->redis_password;

        $this->putJson('/api/v1/settings', $this->validPayload([
            'redis_host' => 'cache-2.internal',
        ]))
            ->assertOk()
            ->assertJsonPath('data.redis_host', 'cache-2.internal')
            ->assertJsonPath('data.has_redis_password', true);

        $this->assertSame($storedPassword, SystemSetting::query()->firstOrFail()->redis_password);
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'cache_driver' => 'redis',
            'redis_client' => 'predis',
            'redis_host' => 'cache.internal',
            'redis_port' => 6379,
            'redis_database' => 0,
            'mail_host' => 'smtp.internal',
            'mail_port' => 587,
            'mail_from_address' => 'noreply@example.com',
            'mail_from_name' => 'Sonetsys',
            'max_upload_size_kb' => 10240,
        ], $overrides);
    }
}
