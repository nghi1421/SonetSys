<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Follow;

use App\Core\Auth\Domain\Models\User;
use App\Modules\Feed\Domain\Models\Post;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class FollowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
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

    public function test_following_a_user_creates_a_row_and_bumps_counts(): void
    {
        $follower = User::factory()->create();
        $followed = User::factory()->create();
        Sanctum::actingAs($follower);

        $this->postJson("/api/v1/users/{$followed->id}/follow")->assertOk();

        $this->assertDatabaseHas('follows', [
            'follower_id' => $follower->id,
            'followed_id' => $followed->id,
        ]);

        $this->getJson("/api/v1/users/{$followed->id}/profile")
            ->assertOk()
            ->assertJsonPath('data.followers_count', 1)
            ->assertJsonPath('data.is_following', true);

        Sanctum::actingAs($followed);
        $this->getJson("/api/v1/users/{$follower->id}/profile")
            ->assertOk()
            ->assertJsonPath('data.following_count', 1);
    }

    public function test_following_the_same_user_twice_is_idempotent(): void
    {
        $follower = User::factory()->create();
        $followed = User::factory()->create();
        Sanctum::actingAs($follower);

        $this->postJson("/api/v1/users/{$followed->id}/follow")->assertOk();
        $this->postJson("/api/v1/users/{$followed->id}/follow")->assertOk();

        $this->assertDatabaseCount('follows', 1);
        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_a_user_cannot_follow_themselves(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/users/{$user->id}/follow")->assertStatus(422);
        $this->assertDatabaseMissing('follows', ['follower_id' => $user->id, 'followed_id' => $user->id]);
    }

    public function test_unfollowing_removes_the_row(): void
    {
        $follower = User::factory()->create();
        $followed = User::factory()->create();
        Sanctum::actingAs($follower);

        $this->postJson("/api/v1/users/{$followed->id}/follow")->assertOk();
        $this->deleteJson("/api/v1/users/{$followed->id}/follow")->assertOk();

        $this->assertDatabaseMissing('follows', [
            'follower_id' => $follower->id,
            'followed_id' => $followed->id,
        ]);
    }

    public function test_followers_and_following_lists_return_the_right_users(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        Sanctum::actingAs($a);
        $this->postJson("/api/v1/users/{$b->id}/follow")->assertOk();

        // Viewed as b (who hasn't followed a back yet): is_following is false.
        Sanctum::actingAs($b);
        $this->getJson("/api/v1/users/{$b->id}/followers")
            ->assertOk()
            ->assertJsonPath('data.0.id', $a->id)
            ->assertJsonPath('data.0.is_following', false);

        // b follows a back — now the same row's is_following flips to true.
        $this->postJson("/api/v1/users/{$a->id}/follow")->assertOk();
        $this->getJson("/api/v1/users/{$b->id}/followers")
            ->assertOk()
            ->assertJsonPath('data.0.is_following', true);

        Sanctum::actingAs($a);
        $this->getJson("/api/v1/users/{$a->id}/following")
            ->assertOk()
            ->assertJsonPath('data.0.id', $b->id);
    }

    public function test_a_notification_is_created_when_followed(): void
    {
        $follower = User::factory()->create();
        $followed = User::factory()->create();
        Sanctum::actingAs($follower);

        $this->postJson("/api/v1/users/{$followed->id}/follow")->assertOk();

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => 'user',
            'notifiable_id' => $followed->id,
            'actor_id' => $follower->id,
            'type' => 'user.followed',
        ]);
    }

    public function test_profile_posts_endpoint_hides_private_posts_from_a_non_author_viewer(): void
    {
        $author = User::factory()->create();
        $publicPost = $this->createPost($author, ['visibility' => 'public']);
        $privatePost = $this->createPost($author, ['visibility' => 'private']);

        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson("/api/v1/users/{$author->id}/posts")->assertOk();
        $postIds = collect($response->json('data'))->pluck('id');

        $this->assertTrue($postIds->contains($publicPost->id));
        $this->assertFalse($postIds->contains($privatePost->id));
    }

    public function test_profile_posts_endpoint_shows_private_posts_to_their_own_author(): void
    {
        $author = User::factory()->create();
        $privatePost = $this->createPost($author, ['visibility' => 'private']);

        Sanctum::actingAs($author);

        $response = $this->getJson("/api/v1/users/{$author->id}/posts")->assertOk();
        $postIds = collect($response->json('data'))->pluck('id');

        $this->assertTrue($postIds->contains($privatePost->id));
    }
}
