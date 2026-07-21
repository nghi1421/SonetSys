<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Feed;

use App\Core\Auth\Domain\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class MentionTest extends TestCase
{
    use RefreshDatabase;

    public function test_mentioning_a_user_on_a_post_attaches_the_mention_and_notifies_them_once(): void
    {
        $author = User::factory()->create();
        $mentioned = User::factory()->create();
        Sanctum::actingAs($author);

        $response = $this->postJson('/api/v1/posts', [
            'body' => 'Great work on this, team!',
            'mentioned_user_ids' => [$mentioned->id],
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.mentions.0.id', $mentioned->id);
        $response->assertJsonPath('data.mentions.0.name', $mentioned->name);

        $this->assertDatabaseHas('mentions', [
            'mentionable_type' => 'post',
            'mentionable_id' => $response->json('data.id'),
            'user_id' => $mentioned->id,
        ]);

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => 'user',
            'notifiable_id' => $mentioned->id,
            'actor_id' => $author->id,
            'type' => 'user.mentioned',
        ]);
        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_mentioning_yourself_is_silently_ignored(): void
    {
        $author = User::factory()->create();
        Sanctum::actingAs($author);

        $response = $this->postJson('/api/v1/posts', [
            'body' => 'Talking to myself here',
            'mentioned_user_ids' => [$author->id],
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.mentions', []);

        $this->assertDatabaseCount('mentions', 0);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_editing_a_post_does_not_send_another_mention_notification(): void
    {
        $author = User::factory()->create();
        $mentioned = User::factory()->create();
        Sanctum::actingAs($author);

        $postId = $this->postJson('/api/v1/posts', [
            'body' => 'First version',
            'mentioned_user_ids' => [$mentioned->id],
        ])->assertCreated()->json('data.id');

        $this->assertDatabaseCount('notifications', 1);

        $this->putJson("/api/v1/posts/{$postId}", ['body' => 'Edited version'])->assertOk();

        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_mentioning_a_user_on_a_comment_attaches_the_mention_and_notifies_them(): void
    {
        $postAuthor = User::factory()->create();
        Sanctum::actingAs($postAuthor);
        $postId = $this->postJson('/api/v1/posts', ['body' => 'Hello world'])
            ->assertCreated()
            ->json('data.id');

        $commenter = User::factory()->create();
        $mentioned = User::factory()->create();
        Sanctum::actingAs($commenter);

        $response = $this->postJson("/api/v1/posts/{$postId}/comments", [
            'body' => 'Check this out!',
            'mentioned_user_ids' => [$mentioned->id],
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.mentions.0.id', $mentioned->id);

        $this->assertDatabaseHas('mentions', [
            'mentionable_type' => 'comment',
            'mentionable_id' => $response->json('data.id'),
            'user_id' => $mentioned->id,
        ]);

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => 'user',
            'notifiable_id' => $mentioned->id,
            'actor_id' => $commenter->id,
            'type' => 'user.mentioned',
        ]);
    }

    public function test_mentioning_multiple_users_notifies_each_of_them_exactly_once(): void
    {
        $author = User::factory()->create();
        $first = User::factory()->create();
        $second = User::factory()->create();
        Sanctum::actingAs($author);

        $this->postJson('/api/v1/posts', [
            'body' => 'Shoutout to the team',
            'mentioned_user_ids' => [$first->id, $second->id],
        ])->assertCreated();

        $this->assertDatabaseCount('notifications', 2);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $first->id, 'type' => 'user.mentioned']);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $second->id, 'type' => 'user.mentioned']);
    }

    public function test_a_nonexistent_mentioned_user_id_is_rejected(): void
    {
        $author = User::factory()->create();
        Sanctum::actingAs($author);

        $response = $this->postJson('/api/v1/posts', [
            'body' => 'Mentioning a ghost',
            'mentioned_user_ids' => [999999],
        ]);

        $response->assertUnprocessable();
    }
}
