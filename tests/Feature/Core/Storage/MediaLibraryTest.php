<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Storage;

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

    public function test_manager_can_list_and_delete_media(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

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
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->postJson('/api/v1/posts', [
            'media' => UploadedFile::fake()->image('photo.jpg'),
            'media_type' => 'image',
        ])->assertCreated();
        $mediaId = $this->getJson('/api/v1/media')->json('data.0.id');

        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/media')->assertForbidden();
        $this->deleteJson("/api/v1/media/{$mediaId}")->assertForbidden();
    }
}
