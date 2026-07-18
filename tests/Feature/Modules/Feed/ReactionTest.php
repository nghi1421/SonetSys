<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Feed;

use App\Core\Auth\Domain\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class ReactionTest extends TestCase
{
    use RefreshDatabase;

    private function createPost(User $author): int
    {
        Sanctum::actingAs($author);

        return $this->postJson('/api/v1/posts', ['body' => 'Hello world'])
            ->assertCreated()
            ->json('data.id');
    }

    private function createComment(User $author, int $postId): int
    {
        Sanctum::actingAs($author);

        return $this->postJson("/api/v1/posts/{$postId}/comments", ['body' => 'Nice post!'])
            ->assertCreated()
            ->json('data.id');
    }

    public function test_reacting_to_a_post_with_a_non_like_type_persists_and_returns_the_right_reaction(): void
    {
        $author = User::factory()->create();
        $postId = $this->createPost($author);

        $reactor = User::factory()->create();
        Sanctum::actingAs($reactor);

        $this->postJson("/api/v1/posts/{$postId}/like", ['type' => 'love'])
            ->assertOk()
            ->assertJsonPath('data.my_reaction', 'love')
            ->assertJsonPath('data.likes_count', 1);

        $this->assertDatabaseHas('interactions', [
            'interactable_type' => 'post',
            'interactable_id' => $postId,
            'user_id' => $reactor->id,
            'type' => 'love',
        ]);
    }

    public function test_reacting_to_a_post_with_no_type_defaults_to_like(): void
    {
        $author = User::factory()->create();
        $postId = $this->createPost($author);

        $reactor = User::factory()->create();
        Sanctum::actingAs($reactor);

        $this->postJson("/api/v1/posts/{$postId}/like")
            ->assertOk()
            ->assertJsonPath('data.my_reaction', 'like')
            ->assertJsonPath('data.likes_count', 1);
    }

    public function test_reacting_with_the_same_type_twice_removes_the_reaction(): void
    {
        $author = User::factory()->create();
        $postId = $this->createPost($author);

        $reactor = User::factory()->create();
        Sanctum::actingAs($reactor);

        $this->postJson("/api/v1/posts/{$postId}/like", ['type' => 'love'])->assertOk();

        $this->postJson("/api/v1/posts/{$postId}/like", ['type' => 'love'])
            ->assertOk()
            ->assertJsonPath('data.my_reaction', null)
            ->assertJsonPath('data.likes_count', 0);

        $this->assertDatabaseMissing('interactions', [
            'interactable_type' => 'post',
            'interactable_id' => $postId,
            'user_id' => $reactor->id,
        ]);
    }

    public function test_switching_reaction_type_updates_my_reaction_without_changing_the_total_count(): void
    {
        $author = User::factory()->create();
        $postId = $this->createPost($author);

        $reactor = User::factory()->create();
        Sanctum::actingAs($reactor);

        $this->postJson("/api/v1/posts/{$postId}/like", ['type' => 'like'])
            ->assertOk()
            ->assertJsonPath('data.likes_count', 1);

        $this->postJson("/api/v1/posts/{$postId}/like", ['type' => 'haha'])
            ->assertOk()
            ->assertJsonPath('data.my_reaction', 'haha')
            ->assertJsonPath('data.likes_count', 1);

        $this->assertDatabaseCount('interactions', 1);
        $this->assertDatabaseHas('interactions', [
            'interactable_type' => 'post',
            'interactable_id' => $postId,
            'user_id' => $reactor->id,
            'type' => 'haha',
        ]);
    }

    public function test_a_notification_is_only_created_for_a_new_reaction_not_for_a_subsequent_type_swap(): void
    {
        $author = User::factory()->create();
        $postId = $this->createPost($author);

        $reactor = User::factory()->create();
        Sanctum::actingAs($reactor);

        $this->postJson("/api/v1/posts/{$postId}/like", ['type' => 'like'])->assertOk();
        $this->assertDatabaseCount('notifications', 1);

        $this->postJson("/api/v1/posts/{$postId}/like", ['type' => 'love'])->assertOk();
        $this->assertDatabaseCount('notifications', 1);

        $this->postJson("/api/v1/posts/{$postId}/like", ['type' => 'wow'])->assertOk();
        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_an_invalid_reaction_type_is_rejected(): void
    {
        $author = User::factory()->create();
        $postId = $this->createPost($author);

        $reactor = User::factory()->create();
        Sanctum::actingAs($reactor);

        $this->postJson("/api/v1/posts/{$postId}/like", ['type' => 'not-a-reaction'])
            ->assertUnprocessable()
            ->assertJsonPath('meta.errors.type.0', fn ($m) => is_string($m));
    }

    public function test_reacting_to_a_comment_persists_the_type_and_bumps_its_count(): void
    {
        $author = User::factory()->create();
        $postId = $this->createPost($author);
        $commentId = $this->createComment($author, $postId);

        $reactor = User::factory()->create();
        Sanctum::actingAs($reactor);

        $this->postJson("/api/v1/comments/{$commentId}/like", ['type' => 'sad'])
            ->assertOk()
            ->assertJsonPath('data.my_reaction', 'sad')
            ->assertJsonPath('data.likes_count', 1);

        $this->assertDatabaseHas('interactions', [
            'interactable_type' => 'comment',
            'interactable_id' => $commentId,
            'user_id' => $reactor->id,
            'type' => 'sad',
        ]);
    }

    public function test_switching_reaction_type_on_a_comment_does_not_change_its_total_count(): void
    {
        $author = User::factory()->create();
        $postId = $this->createPost($author);
        $commentId = $this->createComment($author, $postId);

        $reactor = User::factory()->create();
        Sanctum::actingAs($reactor);

        $this->postJson("/api/v1/comments/{$commentId}/like", ['type' => 'like'])->assertOk();
        $this->postJson("/api/v1/comments/{$commentId}/like", ['type' => 'angry'])
            ->assertOk()
            ->assertJsonPath('data.my_reaction', 'angry')
            ->assertJsonPath('data.likes_count', 1);

        $this->assertDatabaseCount('interactions', 1);
    }

    public function test_the_post_show_endpoint_returns_the_viewers_own_reaction(): void
    {
        $author = User::factory()->create();
        $postId = $this->createPost($author);

        $reactor = User::factory()->create();
        Sanctum::actingAs($reactor);
        $this->postJson("/api/v1/posts/{$postId}/like", ['type' => 'wow'])->assertOk();

        $this->getJson("/api/v1/posts/{$postId}")
            ->assertOk()
            ->assertJsonPath('data.my_reaction', 'wow');
    }

    public function test_the_comment_list_endpoint_returns_the_viewers_own_reaction(): void
    {
        $author = User::factory()->create();
        $postId = $this->createPost($author);
        $commentId = $this->createComment($author, $postId);

        $reactor = User::factory()->create();
        Sanctum::actingAs($reactor);
        $this->postJson("/api/v1/comments/{$commentId}/like", ['type' => 'haha'])->assertOk();

        $this->getJson("/api/v1/posts/{$postId}/comments")
            ->assertOk()
            ->assertJsonPath('data.0.my_reaction', 'haha');
    }
}
