<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Block;

use App\Core\Auth\Domain\Models\User;
use App\Modules\Feed\Domain\Models\Post;
use App\Modules\Follow\Domain\Models\Follow;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class BlockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    private function follow(User $follower, User $followed): void
    {
        Follow::query()->create([
            'follower_id' => $follower->id,
            'followed_id' => $followed->id,
            'created_at' => now(),
        ]);
    }

    private function makeMutualFollowers(): array
    {
        $a = User::factory()->create();
        $b = User::factory()->create();

        $this->follow($a, $b);
        $this->follow($b, $a);

        return [$a, $b];
    }

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

    public function test_blocking_a_user_removes_an_existing_mutual_follow_in_both_directions(): void
    {
        [$a, $b] = $this->makeMutualFollowers();
        Sanctum::actingAs($a);

        $this->postJson("/api/v1/users/{$b->id}/block")->assertOk();

        $this->assertDatabaseMissing('follows', ['follower_id' => $a->id, 'followed_id' => $b->id]);
        $this->assertDatabaseMissing('follows', ['follower_id' => $b->id, 'followed_id' => $a->id]);

        $this->assertDatabaseHas('blocks', ['blocker_id' => $a->id, 'blocked_id' => $b->id]);
    }

    public function test_a_blocked_or_blocking_user_cannot_newly_follow_the_other_in_either_direction(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        Sanctum::actingAs($a);
        $this->postJson("/api/v1/users/{$b->id}/block")->assertOk();

        // Blocker attempting to follow the blocked user.
        $this->postJson("/api/v1/users/{$b->id}/follow")->assertStatus(422);

        // Blocked user attempting to follow the blocker.
        Sanctum::actingAs($b);
        $this->postJson("/api/v1/users/{$a->id}/follow")->assertStatus(422);

        $this->assertDatabaseCount('follows', 0);
    }

    public function test_self_block_is_rejected(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/users/{$user->id}/block")->assertStatus(422);
        $this->assertDatabaseMissing('blocks', ['blocker_id' => $user->id, 'blocked_id' => $user->id]);
    }

    public function test_a_blocked_authors_posts_are_excluded_from_the_main_feed(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create();
        $post = $this->createPost($author);

        Sanctum::actingAs($viewer);
        $this->postJson("/api/v1/users/{$author->id}/block")->assertOk();

        $response = $this->getJson('/api/v1/posts')->assertOk();
        $postIds = collect($response->json('data'))->pluck('id');

        $this->assertFalse($postIds->contains($post->id));
    }

    public function test_a_blocked_authors_posts_are_excluded_from_the_following_feed(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create();
        $this->follow($viewer, $author);
        $post = $this->createPost($author);

        Sanctum::actingAs($viewer);
        $this->postJson("/api/v1/users/{$author->id}/block")->assertOk();

        $response = $this->getJson('/api/v1/posts/following')->assertOk();
        $postIds = collect($response->json('data'))->pluck('id');

        $this->assertFalse($postIds->contains($post->id));
    }

    public function test_a_blocked_authors_posts_are_excluded_from_the_hashtag_feed(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create();

        Sanctum::actingAs($author);
        $postId = $this->postJson('/api/v1/posts', ['body' => 'Check out #laravel today'])
            ->assertCreated()
            ->json('data.id');

        Sanctum::actingAs($viewer);
        $this->postJson("/api/v1/users/{$author->id}/block")->assertOk();

        $response = $this->getJson('/api/v1/hashtags/laravel/posts')->assertOk();
        $postIds = collect($response->json('data'))->pluck('id');

        $this->assertFalse($postIds->contains($postId));
    }

    public function test_a_blocked_authors_posts_are_excluded_from_their_own_profile_post_list(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create();
        $post = $this->createPost($author);

        Sanctum::actingAs($viewer);
        $this->postJson("/api/v1/users/{$author->id}/block")->assertOk();

        $response = $this->getJson("/api/v1/users/{$author->id}/posts")->assertOk();
        $postIds = collect($response->json('data'))->pluck('id');

        $this->assertFalse($postIds->contains($post->id));
    }

    public function test_unblocking_restores_visibility_across_feeds(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create();
        $post = $this->createPost($author);

        Sanctum::actingAs($viewer);
        $this->postJson("/api/v1/users/{$author->id}/block")->assertOk();
        $this->deleteJson("/api/v1/users/{$author->id}/block")->assertOk();

        $this->assertDatabaseMissing('blocks', ['blocker_id' => $viewer->id, 'blocked_id' => $author->id]);

        $response = $this->getJson('/api/v1/posts')->assertOk();
        $postIds = collect($response->json('data'))->pluck('id');

        $this->assertTrue($postIds->contains($post->id));
    }

    public function test_blocking_prevents_starting_a_new_chat_conversation(): void
    {
        [$a, $b] = $this->makeMutualFollowers();
        Sanctum::actingAs($a);
        $this->postJson("/api/v1/users/{$b->id}/block")->assertOk();

        $this->postJson("/api/v1/users/{$b->id}/conversations")->assertStatus(422);
        $this->assertDatabaseCount('conversations', 0);
    }

    public function test_blocking_an_existing_conversation_partner_prevents_further_messages(): void
    {
        [$a, $b] = $this->makeMutualFollowers();
        Sanctum::actingAs($a);
        $conversationId = $this->postJson("/api/v1/users/{$b->id}/conversations")
            ->assertCreated()
            ->json('data.id');

        $this->postJson("/api/v1/conversations/{$conversationId}/messages", ['body' => 'Hi there'])
            ->assertCreated();

        $this->postJson("/api/v1/users/{$b->id}/block")->assertOk();

        $this->postJson("/api/v1/conversations/{$conversationId}/messages", ['body' => 'Are you there?'])
            ->assertStatus(422);
    }

    public function test_mentioning_a_blocked_user_is_silently_filtered_out(): void
    {
        $author = User::factory()->create();
        $blocked = User::factory()->create();
        Sanctum::actingAs($author);
        $this->postJson("/api/v1/users/{$blocked->id}/block")->assertOk();

        $response = $this->postJson('/api/v1/posts', [
            'body' => 'Hello there',
            'mentioned_user_ids' => [$blocked->id],
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.mentions', []);

        $this->assertDatabaseCount('mentions', 0);
        $this->assertDatabaseMissing('notifications', ['notifiable_id' => $blocked->id, 'type' => 'user.mentioned']);
    }

    public function test_mentioning_a_user_who_has_blocked_you_is_silently_filtered_out(): void
    {
        $author = User::factory()->create();
        $blocker = User::factory()->create();
        Sanctum::actingAs($blocker);
        $this->postJson("/api/v1/users/{$author->id}/block")->assertOk();

        Sanctum::actingAs($author);
        $response = $this->postJson('/api/v1/posts', [
            'body' => 'Hello there',
            'mentioned_user_ids' => [$blocker->id],
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.mentions', []);
        $this->assertDatabaseCount('mentions', 0);
    }

    public function test_blocked_users_endpoint_lists_who_you_have_blocked_and_unblock_removes_them(): void
    {
        $viewer = User::factory()->create();
        $blocked = User::factory()->create();
        Sanctum::actingAs($viewer);

        $this->postJson("/api/v1/users/{$blocked->id}/block")->assertOk();

        $this->getJson('/api/v1/blocked-users')
            ->assertOk()
            ->assertJsonPath('data.0.id', $blocked->id);

        $this->deleteJson("/api/v1/users/{$blocked->id}/block")->assertOk();

        $this->getJson('/api/v1/blocked-users')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_profile_endpoint_reports_is_blocked_and_is_blocked_by(): void
    {
        $viewer = User::factory()->create();
        $blocked = User::factory()->create();
        Sanctum::actingAs($viewer);
        $this->postJson("/api/v1/users/{$blocked->id}/block")->assertOk();

        $this->getJson("/api/v1/users/{$blocked->id}/profile")
            ->assertOk()
            ->assertJsonPath('data.is_blocked', true)
            ->assertJsonPath('data.is_blocked_by', false);

        Sanctum::actingAs($blocked);
        $this->getJson("/api/v1/users/{$viewer->id}/profile")
            ->assertOk()
            ->assertJsonPath('data.is_blocked', false)
            ->assertJsonPath('data.is_blocked_by', true);
    }
}
