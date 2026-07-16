<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Feed;

use App\Core\Auth\Domain\Models\User;
use App\Core\Storage\Domain\Models\Media;
use App\Modules\Feed\Domain\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class PostMediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_uploading_an_image_stores_it_on_the_local_disk_and_records_a_media_row(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $file = UploadedFile::fake()->image('photo.jpg');

        $response = $this->postJson('/api/v1/posts', [
            'media' => $file,
            'media_type' => 'image',
        ]);

        $response->assertCreated();
        $postId = $response->json('data.id');

        $post = Post::query()->findOrFail($postId);
        $this->assertSame('local', $post->media_disk);
        Storage::disk('public')->assertExists($post->media_path);

        $media = Media::query()->where('mediable_type', $post->getMorphClass())->where('mediable_id', $post->id)->first();
        $this->assertNotNull($media);
        $this->assertSame('local', $media->disk);
        $this->assertSame($post->media_path, $media->path);
        $this->assertSame('image', $media->type->value);
        $this->assertSame($user->tenant_id, $media->tenant_id);
        $this->assertSame($user->id, $media->uploaded_by);

        $response->assertJsonPath('data.media_url', fn ($url) => str_contains((string) $url, $post->media_path));
    }

    public function test_deleting_a_post_deletes_its_media_file_and_media_row(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $postId = $this->postJson('/api/v1/posts', [
            'media' => UploadedFile::fake()->image('photo.jpg'),
            'media_type' => 'image',
        ])->assertCreated()->json('data.id');

        $post = Post::query()->findOrFail($postId);
        $path = $post->media_path;
        Storage::disk('public')->assertExists($path);

        $this->deleteJson("/api/v1/posts/{$postId}")->assertOk();

        Storage::disk('public')->assertMissing($path);
        $this->assertDatabaseMissing('media', ['mediable_id' => $postId, 'mediable_type' => $post->getMorphClass()]);
    }

    public function test_deleting_a_legacy_post_with_no_media_row_still_deletes_its_file(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Simulate a post created before the `media` table existed: media_path
        // is set directly on the post, with no corresponding Media row.
        $legacyPath = 'posts/'.$user->tenant_id.'/legacy.jpg';
        Storage::disk('public')->put($legacyPath, 'fake-image-bytes');

        $post = Post::query()->create([
            'tenant_id' => $user->tenant_id,
            'author_id' => $user->id,
            'body' => 'Legacy post',
            'visibility' => 'public',
            'metadata' => [],
            'media_type' => 'image',
            'media_path' => $legacyPath,
            'media_disk' => null,
            'published_at' => now(),
        ]);

        $this->assertDatabaseMissing('media', ['mediable_id' => $post->id]);

        $this->deleteJson("/api/v1/posts/{$post->id}")->assertOk();

        Storage::disk('public')->assertMissing($legacyPath);
    }
}
