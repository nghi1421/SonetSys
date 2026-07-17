<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Feed;

use App\Core\Auth\Domain\Models\User;
use App\Modules\Feed\Domain\Models\Post;
use App\Modules\Follow\Domain\Models\Follow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class FollowingFeedTest extends TestCase
{
    use RefreshDatabase;

    private function createPost(User $author, array $overrides = []): Post
    {
        return Post::query()->create([
            'author_id' => $author->id,
            'body' => 'Hello world',
            'visibility' => 'public',
            'metadata' => [],
            'published_at' => now(),
            ...$overrides,
        ]);
    }

    private function follow(User $follower, User $followed): void
    {
        Follow::query()->create([
            'follower_id' => $follower->id,
            'followed_id' => $followed->id,
            'created_at' => now(),
        ]);
    }

    public function test_following_feed_only_shows_posts_from_followed_authors(): void
    {
        $viewer = User::factory()->create();
        $followed = User::factory()->create();
        $stranger = User::factory()->create();

        $this->follow($viewer, $followed);

        $followedPost = $this->createPost($followed);
        $this->createPost($stranger);

        Sanctum::actingAs($viewer);

        $response = $this->getJson('/api/v1/posts/following')->assertOk();
        $postIds = collect($response->json('data'))->pluck('id');

        $this->assertSame([$followedPost->id], $postIds->all());
    }

    public function test_following_feed_hides_a_followed_authors_private_post(): void
    {
        $viewer = User::factory()->create();
        $followed = User::factory()->create();
        $this->follow($viewer, $followed);

        $publicPost = $this->createPost($followed, ['visibility' => 'public']);
        $this->createPost($followed, ['visibility' => 'private']);

        Sanctum::actingAs($viewer);

        $response = $this->getJson('/api/v1/posts/following')->assertOk();
        $postIds = collect($response->json('data'))->pluck('id');

        $this->assertSame([$publicPost->id], $postIds->all());
    }

    public function test_following_feed_is_empty_when_not_following_anyone(): void
    {
        $viewer = User::factory()->create();
        $this->createPost(User::factory()->create());

        Sanctum::actingAs($viewer);

        $this->getJson('/api/v1/posts/following')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
