<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Feed;

use App\Core\Auth\Domain\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class FeedCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_newly_created_post_appears_in_the_site_feed_even_after_the_feed_was_cached(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Populate the cache with an empty feed.
        $this->getJson('/api/v1/posts')->assertOk()->assertJsonCount(0, 'data');

        $this->postJson('/api/v1/posts', ['body' => 'Hello world'])->assertCreated();

        $this->getJson('/api/v1/posts')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_updating_a_post_is_reflected_in_the_site_feed_even_after_the_feed_was_cached(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $postId = $this->postJson('/api/v1/posts', ['body' => 'Original body'])
            ->assertCreated()
            ->json('data.id');

        // Populate the cache with the original body.
        $this->getJson('/api/v1/posts')->assertJsonPath('data.0.body', 'Original body');

        $this->putJson("/api/v1/posts/{$postId}", ['body' => 'Edited body'])->assertOk();

        $this->getJson('/api/v1/posts')->assertJsonPath('data.0.body', 'Edited body');
    }

    public function test_deleting_a_post_removes_it_from_the_site_feed_even_after_the_feed_was_cached(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $postId = $this->postJson('/api/v1/posts', ['body' => 'Temporary'])
            ->assertCreated()
            ->json('data.id');

        $this->getJson('/api/v1/posts')->assertJsonCount(1, 'data');

        $this->deleteJson("/api/v1/posts/{$postId}")->assertOk();

        $this->getJson('/api/v1/posts')->assertJsonCount(0, 'data');
    }

    public function test_a_private_post_cached_for_its_author_does_not_leak_to_another_viewers_cached_feed(): void
    {
        $author = User::factory()->create();

        Sanctum::actingAs($author);
        $this->postJson('/api/v1/posts', ['body' => 'My private thought', 'visibility' => 'private'])
            ->assertCreated();

        // Author's feed is cached and (correctly) includes their own private post.
        $this->getJson('/api/v1/posts')->assertOk()->assertJsonCount(1, 'data');

        $bystander = User::factory()->create();
        Sanctum::actingAs($bystander);

        // A different viewer's cached feed key must not reuse the author's
        // cache entry, or the private post would leak across viewers.
        $this->getJson('/api/v1/posts')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_a_newly_created_post_appears_in_the_group_feed_even_after_it_was_cached(): void
    {
        $owner = User::factory()->create();
        Sanctum::actingAs($owner);

        $groupId = $this->postJson('/api/v1/groups', ['name' => 'Book Club'])
            ->assertCreated()
            ->json('data.id');

        // Populate the cache with an empty group feed.
        $this->getJson("/api/v1/groups/{$groupId}/posts")->assertOk()->assertJsonCount(0, 'data');

        $this->postJson("/api/v1/groups/{$groupId}/posts", ['body' => 'Hello group'])->assertCreated();

        $this->getJson("/api/v1/groups/{$groupId}/posts")->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_resharing_a_post_updates_its_shares_count_in_the_site_feed_even_after_it_was_cached(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $originalId = $this->postJson('/api/v1/posts', ['body' => 'Original post'])
            ->assertCreated()
            ->json('data.id');

        // Populate the cache with shares_count = 0.
        $this->getJson('/api/v1/posts')->assertJsonPath('data.0.shares_count', 0);

        $reshareId = $this->postJson('/api/v1/posts', ['shared_post_id' => $originalId])
            ->assertCreated()
            ->json('data.id');

        $feed = $this->getJson('/api/v1/posts')->assertOk()->json('data');
        $original = collect($feed)->firstWhere('id', $originalId);
        $this->assertSame(1, $original['shares_count']);

        $this->deleteJson("/api/v1/posts/{$reshareId}")->assertOk();

        $feed = $this->getJson('/api/v1/posts')->assertOk()->json('data');
        $original = collect($feed)->firstWhere('id', $originalId);
        $this->assertSame(0, $original['shares_count']);
    }
}
