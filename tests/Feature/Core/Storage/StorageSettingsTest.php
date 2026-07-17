<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Storage;

use App\Core\Auth\Domain\Models\User;
use App\Core\Storage\Domain\Models\StorageSetting;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class StorageSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    public function test_default_storage_config_is_local(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->getJson('/api/v1/storage/settings')
            ->assertOk()
            ->assertJsonPath('data.driver', 'local')
            ->assertJsonPath('data.has_secret', false);
    }

    public function test_non_manager_cannot_view_or_update_storage_settings(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/storage/settings')->assertForbidden();
        $this->putJson('/api/v1/storage/settings', ['driver' => 'local'])->assertForbidden();
    }

    public function test_rejects_an_s3_endpoint_pointing_at_an_internal_address(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->putJson('/api/v1/storage/settings', [
            'driver' => 's3',
            'bucket' => 'my-bucket',
            'region' => 'us-east-1',
            'key' => 'AKIAEXAMPLE',
            'secret' => 'super-secret-value',
            'endpoint' => 'http://169.254.169.254/',
        ])->assertStatus(422)->assertJsonPath('meta.errors.endpoint.0', fn ($message) => is_string($message));

        $this->putJson('/api/v1/storage/settings', [
            'driver' => 's3',
            'bucket' => 'my-bucket',
            'region' => 'us-east-1',
            'key' => 'AKIAEXAMPLE',
            'secret' => 'super-secret-value',
            'endpoint' => 'http://localhost:6379/',
        ])->assertStatus(422)->assertJsonPath('meta.errors.endpoint.0', fn ($message) => is_string($message));
    }

    public function test_manager_can_switch_to_s3_and_the_secret_is_never_exposed(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $response = $this->putJson('/api/v1/storage/settings', [
            'driver' => 's3',
            'bucket' => 'my-bucket',
            'region' => 'us-east-1',
            'key' => 'AKIAEXAMPLE',
            'secret' => 'super-secret-value',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.driver', 's3');
        $response->assertJsonPath('data.bucket', 'my-bucket');
        $response->assertJsonPath('data.has_secret', true);
        $response->assertJsonMissingPath('data.secret');

        $raw = json_encode($response->json());
        $this->assertStringNotContainsString('super-secret-value', (string) $raw);
    }

    public function test_updating_config_without_a_secret_keeps_the_previously_saved_one(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->putJson('/api/v1/storage/settings', [
            'driver' => 's3',
            'bucket' => 'my-bucket',
            'region' => 'us-east-1',
            'key' => 'AKIAEXAMPLE',
            'secret' => 'super-secret-value',
        ])->assertOk();

        $storedSecret = StorageSetting::query()->firstOrFail()->secret;

        // Update the bucket only, without resending the secret.
        $this->putJson('/api/v1/storage/settings', [
            'driver' => 's3',
            'bucket' => 'renamed-bucket',
            'region' => 'us-east-1',
            'key' => 'AKIAEXAMPLE',
        ])
            ->assertOk()
            ->assertJsonPath('data.bucket', 'renamed-bucket')
            ->assertJsonPath('data.has_secret', true);

        $this->assertSame($storedSecret, StorageSetting::query()->firstOrFail()->secret);
    }
}
