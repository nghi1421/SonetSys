<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Auth;

use App\Core\Auth\Domain\Models\User;
use App\Core\Storage\Domain\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_uploading_an_avatar_stores_the_file_and_updates_avatar_url(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/profile', [
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
        ]);

        $response->assertOk();

        $user->refresh();
        $this->assertSame('local', $user->avatar_disk);
        $this->assertNotNull($user->avatar_path);
        Storage::disk('public')->assertExists($user->avatar_path);
        $response->assertJsonPath('data.avatar_url', fn ($url) => str_contains((string) $url, $user->avatar_path));

        $media = Media::query()
            ->where('mediable_type', $user->getMorphClass())
            ->where('mediable_id', $user->id)
            ->first();
        $this->assertNotNull($media);
        $this->assertSame('image', $media->type->value);
        $this->assertSame($user->id, $media->uploaded_by);
    }

    public function test_uploading_a_second_avatar_deletes_the_first_file_from_storage(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/profile', [
            'avatar' => UploadedFile::fake()->image('first.jpg'),
        ])->assertOk();

        $firstPath = $user->refresh()->avatar_path;
        Storage::disk('public')->assertExists($firstPath);

        $this->postJson('/api/v1/profile', [
            'avatar' => UploadedFile::fake()->image('second.jpg'),
        ])->assertOk();

        $user->refresh();
        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($user->avatar_path);
        $this->assertNotSame($firstPath, $user->avatar_path);
    }

    public function test_uploading_a_cover_stores_the_file_and_updates_cover_url(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/profile', [
            'cover' => UploadedFile::fake()->image('cover.jpg'),
        ]);

        $response->assertOk();

        $user->refresh();
        $this->assertSame('local', $user->cover_disk);
        $this->assertNotNull($user->cover_path);
        Storage::disk('public')->assertExists($user->cover_path);
        $response->assertJsonPath('data.cover_url', fn ($url) => str_contains((string) $url, $user->cover_path));
    }

    public function test_uploading_a_second_cover_deletes_the_first_file_from_storage(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/profile', [
            'cover' => UploadedFile::fake()->image('first-cover.jpg'),
        ])->assertOk();

        $firstPath = $user->refresh()->cover_path;
        Storage::disk('public')->assertExists($firstPath);

        $this->postJson('/api/v1/profile', [
            'cover' => UploadedFile::fake()->image('second-cover.jpg'),
        ])->assertOk();

        $user->refresh();
        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($user->cover_path);
    }

    public function test_removing_an_avatar_deletes_the_file_and_nulls_the_fields(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/profile', [
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
        ])->assertOk();

        $path = $user->refresh()->avatar_path;

        $response = $this->postJson('/api/v1/profile', [
            'remove_avatar' => true,
        ]);

        $response->assertOk();
        Storage::disk('public')->assertMissing($path);

        $user->refresh();
        $this->assertNull($user->avatar_url);
        $this->assertNull($user->avatar_disk);
        $this->assertNull($user->avatar_path);
    }

    public function test_removing_a_cover_deletes_the_file_and_nulls_the_fields(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/profile', [
            'cover' => UploadedFile::fake()->image('cover.jpg'),
        ])->assertOk();

        $path = $user->refresh()->cover_path;

        $response = $this->postJson('/api/v1/profile', [
            'remove_cover' => true,
        ]);

        $response->assertOk();
        Storage::disk('public')->assertMissing($path);

        $user->refresh();
        $this->assertNull($user->cover_url);
        $this->assertNull($user->cover_disk);
        $this->assertNull($user->cover_path);
    }

    public function test_an_invalid_file_type_is_rejected(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/profile', [
            'avatar' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        ])->assertStatus(422);
    }

    public function test_an_oversized_file_is_rejected(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/profile', [
            'avatar' => UploadedFile::fake()->create('big.jpg', 6000, 'image/jpeg'),
        ])->assertStatus(422);
    }

    public function test_an_unauthenticated_request_is_rejected(): void
    {
        Storage::fake('public');

        $this->postJson('/api/v1/profile', [
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
        ])->assertStatus(401);
    }

    public function test_uploading_only_a_cover_leaves_the_existing_avatar_untouched(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/profile', [
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
        ])->assertOk();

        $avatarPath = $user->refresh()->avatar_path;

        $this->postJson('/api/v1/profile', [
            'cover' => UploadedFile::fake()->image('cover.jpg'),
        ])->assertOk();

        $user->refresh();
        $this->assertSame($avatarPath, $user->avatar_path);
        Storage::disk('public')->assertExists($avatarPath);
        $this->assertNotNull($user->cover_path);
    }

    public function test_uploading_only_an_avatar_leaves_the_existing_cover_untouched(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/profile', [
            'cover' => UploadedFile::fake()->image('cover.jpg'),
        ])->assertOk();

        $coverPath = $user->refresh()->cover_path;

        $this->postJson('/api/v1/profile', [
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
        ])->assertOk();

        $user->refresh();
        $this->assertSame($coverPath, $user->cover_path);
        Storage::disk('public')->assertExists($coverPath);
        $this->assertNotNull($user->avatar_path);
    }

    public function test_a_single_request_can_replace_an_avatar_and_remove_the_cover_independently(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/profile', [
            'cover' => UploadedFile::fake()->image('cover.jpg'),
        ])->assertOk();

        $coverPath = $user->refresh()->cover_path;

        $response = $this->postJson('/api/v1/profile', [
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
            'remove_cover' => true,
        ]);

        $response->assertOk();

        $user->refresh();
        Storage::disk('public')->assertMissing($coverPath);
        $this->assertNull($user->cover_url);
        $this->assertNotNull($user->avatar_path);
        Storage::disk('public')->assertExists($user->avatar_path);
    }
}
