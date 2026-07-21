<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Feed;

use App\Core\Auth\Domain\Models\User;
use App\Modules\Feed\Domain\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class ReelTest extends TestCase
{
    use RefreshDatabase;

    private function createReel(User $author, string $body = 'A short video'): int
    {
        Storage::fake('public');
        Sanctum::actingAs($author);

        return $this->postJson('/api/v1/reels', [
            'media' => UploadedFile::fake()->create('clip.mp4', 500, 'video/mp4'),
            'body' => $body,
        ])
            ->assertCreated()
            ->json('data.id');
    }

    public function test_creating_a_reel_requires_a_video_file(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/reels', ['body' => 'No media'])
            ->assertUnprocessable();
    }

    public function test_creating_a_reel_rejects_an_image_file(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/reels', [
            'media' => UploadedFile::fake()->image('photo.jpg'),
        ])
            ->assertUnprocessable();
    }

    public function test_creating_a_reel_persists_is_reel_and_video_media_type_with_no_group(): void
    {
        $author = User::factory()->create();
        $reelId = $this->createReel($author);

        $reel = Post::query()->findOrFail($reelId);

        $this->assertTrue($reel->is_reel);
        $this->assertSame('video', $reel->media_type->value);
        $this->assertNull($reel->group_id);
    }

    public function test_reels_feed_only_returns_reels_excluding_regular_video_and_image_posts(): void
    {
        $author = User::factory()->create();
        $reelId = $this->createReel($author);

        Storage::fake('public');
        Sanctum::actingAs($author);
        $this->postJson('/api/v1/posts', [
            'media' => UploadedFile::fake()->create('regular.mp4', 500, 'video/mp4'),
            'media_type' => 'video',
        ])->assertCreated();
        $this->postJson('/api/v1/posts', [
            'media' => UploadedFile::fake()->image('regular.jpg'),
            'media_type' => 'image',
        ])->assertCreated();

        $viewer = User::factory()->create();
        Sanctum::actingAs($viewer);

        $response = $this->getJson('/api/v1/reels')->assertOk();

        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $reelId);
    }

    public function test_reels_feed_cursor_pagination_advances(): void
    {
        $author = User::factory()->create();
        $firstReelId = $this->createReel($author, 'First');
        $secondReelId = $this->createReel($author, 'Second');

        $viewer = User::factory()->create();
        Sanctum::actingAs($viewer);

        $firstPage = $this->getJson('/api/v1/reels?limit=1')->assertOk();
        $firstPage->assertJsonCount(1, 'data');
        $firstPage->assertJsonPath('data.0.id', $secondReelId);
        $cursor = $firstPage->json('meta.next_cursor');
        $this->assertNotNull($cursor);

        $secondPage = $this->getJson('/api/v1/reels?limit=1&cursor='.urlencode($cursor))->assertOk();
        $secondPage->assertJsonCount(1, 'data');
        $secondPage->assertJsonPath('data.0.id', $firstReelId);
    }

    public function test_a_reel_can_be_liked_and_commented_on_via_the_existing_post_endpoints(): void
    {
        $author = User::factory()->create();
        $reelId = $this->createReel($author);

        $viewer = User::factory()->create();
        Sanctum::actingAs($viewer);

        $this->postJson("/api/v1/posts/{$reelId}/like")
            ->assertOk()
            ->assertJsonPath('data.likes_count', 1);

        $this->postJson("/api/v1/posts/{$reelId}/comments", ['body' => 'Nice reel!'])
            ->assertCreated();
    }
}
