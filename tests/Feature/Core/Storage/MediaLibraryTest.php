<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Storage;

use App\Core\Auth\Domain\Models\Role;
use App\Core\Auth\Domain\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class MediaLibraryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        Storage::fake('public');
    }

    public function test_manager_can_list_and_delete_media_for_their_tenant(): void
    {
        $admin = $this->tenantAdminUser();

        Sanctum::actingAs($admin);
        $postId = $this->postJson('/api/v1/posts', [
            'media' => UploadedFile::fake()->image('photo.jpg'),
            'media_type' => 'image',
        ])->assertCreated()->json('data.id');

        $list = $this->getJson('/api/v1/media')->assertOk();
        $list->assertJsonCount(1, 'data');
        $mediaId = $list->json('data.0.id');

        $this->deleteJson("/api/v1/media/{$mediaId}")->assertOk();

        $this->assertDatabaseMissing('media', ['id' => $mediaId]);
        $this->assertDatabaseHas('posts', ['id' => $postId]);
    }

    public function test_non_manager_cannot_list_or_delete_media(): void
    {
        $admin = $this->tenantAdminUser();
        Sanctum::actingAs($admin);
        $this->postJson('/api/v1/posts', [
            'media' => UploadedFile::fake()->image('photo.jpg'),
            'media_type' => 'image',
        ])->assertCreated();
        $mediaId = $this->getJson('/api/v1/media')->json('data.0.id');

        Sanctum::actingAs(User::factory()->forTenant($admin->tenant)->create());

        $this->getJson('/api/v1/media')->assertForbidden();
        $this->deleteJson("/api/v1/media/{$mediaId}")->assertForbidden();
    }

    public function test_a_tenant_admin_cannot_see_or_delete_another_tenants_media(): void
    {
        $ownerAdmin = $this->tenantAdminUser();
        Sanctum::actingAs($ownerAdmin);
        $this->postJson('/api/v1/posts', [
            'media' => UploadedFile::fake()->image('photo.jpg'),
            'media_type' => 'image',
        ])->assertCreated();
        $mediaId = $this->getJson('/api/v1/media')->json('data.0.id');

        $otherAdmin = $this->tenantAdminUser();
        Sanctum::actingAs($otherAdmin);

        $this->getJson('/api/v1/media')->assertOk()->assertJsonCount(0, 'data');
        $this->deleteJson("/api/v1/media/{$mediaId}")->assertNotFound();
    }

    private function tenantAdminUser(): User
    {
        $role = Role::factory()->tenantAdmin()->create();

        return User::factory()->create([
            'tenant_id' => $role->tenant_id,
            'role_id' => $role->id,
        ]);
    }
}
