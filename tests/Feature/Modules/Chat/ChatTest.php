<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Chat;

use App\Core\Auth\Domain\Models\User;
use App\Modules\Chat\Domain\Models\Conversation;
use App\Modules\Chat\Domain\Models\Message;
use App\Modules\Follow\Domain\Models\Follow;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class ChatTest extends TestCase
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

    public function test_starting_a_conversation_between_mutual_followers_succeeds_and_is_idempotent(): void
    {
        [$a, $b] = $this->makeMutualFollowers();
        Sanctum::actingAs($a);

        $first = $this->postJson("/api/v1/users/{$b->id}/conversations")->assertCreated();
        $conversationId = $first->json('data.id');

        $second = $this->postJson("/api/v1/users/{$b->id}/conversations")->assertCreated();

        $this->assertSame($conversationId, $second->json('data.id'));
        $this->assertDatabaseCount('conversations', 1);
    }

    public function test_starting_a_conversation_without_mutual_follow_is_rejected(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        Sanctum::actingAs($a);

        // No follow relationship at all.
        $this->postJson("/api/v1/users/{$b->id}/conversations")->assertStatus(422);

        // One-directional follow only.
        $this->follow($a, $b);
        $this->postJson("/api/v1/users/{$b->id}/conversations")->assertStatus(422);

        $this->assertDatabaseCount('conversations', 0);
    }

    public function test_a_user_cannot_message_themselves(): void
    {
        $a = User::factory()->create();
        Sanctum::actingAs($a);

        $this->postJson("/api/v1/users/{$a->id}/conversations")->assertStatus(422);
    }

    public function test_sending_a_message_persists_it_bumps_last_message_at_and_returns_it_in_the_thread(): void
    {
        [$a, $b] = $this->makeMutualFollowers();
        Sanctum::actingAs($a);

        $conversationId = $this->postJson("/api/v1/users/{$b->id}/conversations")
            ->assertCreated()
            ->json('data.id');

        $this->postJson("/api/v1/conversations/{$conversationId}/messages", ['body' => 'Hello there'])
            ->assertCreated()
            ->assertJsonPath('data.body', 'Hello there')
            ->assertJsonPath('data.sender_id', $a->id);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversationId,
            'sender_id' => $a->id,
            'body' => 'Hello there',
        ]);

        $conversation = Conversation::query()->find($conversationId);
        $this->assertNotNull($conversation->last_message_at);

        $thread = $this->getJson("/api/v1/conversations/{$conversationId}/messages")->assertOk();
        $this->assertSame('Hello there', $thread->json('data.0.body'));
    }

    public function test_a_non_participant_gets_403_fetching_or_sending_in_someone_elses_conversation(): void
    {
        [$a, $b] = $this->makeMutualFollowers();
        Sanctum::actingAs($a);

        $conversationId = $this->postJson("/api/v1/users/{$b->id}/conversations")
            ->assertCreated()
            ->json('data.id');

        $stranger = User::factory()->create();
        Sanctum::actingAs($stranger);

        $this->getJson("/api/v1/conversations/{$conversationId}/messages")->assertForbidden();
        $this->postJson("/api/v1/conversations/{$conversationId}/messages", ['body' => 'Hi'])->assertForbidden();
    }

    public function test_listing_conversations_returns_them_ordered_by_recency_with_correct_other_participant(): void
    {
        [$a, $b] = $this->makeMutualFollowers();
        $c = User::factory()->create();
        $this->follow($a, $c);
        $this->follow($c, $a);

        Sanctum::actingAs($a);

        $conversationWithB = $this->postJson("/api/v1/users/{$b->id}/conversations")->assertCreated()->json('data.id');
        $conversationWithC = $this->postJson("/api/v1/users/{$c->id}/conversations")->assertCreated()->json('data.id');

        // Bump conversation with B to be the most recent by sending a message on it last.
        $this->postJson("/api/v1/conversations/{$conversationWithC}/messages", ['body' => 'Hi C']);
        $this->postJson("/api/v1/conversations/{$conversationWithB}/messages", ['body' => 'Hi B']);

        $response = $this->getJson('/api/v1/conversations')->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->all();

        $this->assertSame([$conversationWithB, $conversationWithC], $ids);
        $this->assertSame($b->id, $response->json('data.0.other_participant.id'));
    }

    public function test_unread_count_reflects_messages_from_the_other_participant_and_resets_on_read(): void
    {
        [$a, $b] = $this->makeMutualFollowers();
        Sanctum::actingAs($a);
        $conversationId = $this->postJson("/api/v1/users/{$b->id}/conversations")->assertCreated()->json('data.id');

        Sanctum::actingAs($b);
        $this->postJson("/api/v1/conversations/{$conversationId}/messages", ['body' => 'Hey!'])->assertCreated();

        Sanctum::actingAs($a);
        $this->getJson('/api/v1/conversations/unread-count')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 1);

        // Fetching the thread marks it read.
        $this->getJson("/api/v1/conversations/{$conversationId}/messages")->assertOk();

        $this->getJson('/api/v1/conversations/unread-count')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 0);
    }

    public function test_cursor_pagination_of_a_long_thread_returns_older_messages_correctly(): void
    {
        [$a, $b] = $this->makeMutualFollowers();
        Sanctum::actingAs($a);
        $conversationId = $this->postJson("/api/v1/users/{$b->id}/conversations")->assertCreated()->json('data.id');

        // Seed messages with strictly increasing timestamps so ordering is deterministic.
        $bodies = [];
        for ($i = 0; $i < 5; $i++) {
            $body = "message-{$i}";
            $bodies[] = $body;
            Message::query()->create([
                'conversation_id' => $conversationId,
                'sender_id' => $a->id,
                'body' => $body,
                'created_at' => now()->addSeconds($i),
                'updated_at' => now()->addSeconds($i),
            ]);
        }

        $firstPage = $this->getJson("/api/v1/conversations/{$conversationId}/messages?limit=3")->assertOk();
        $firstBodies = collect($firstPage->json('data'))->pluck('body')->all();
        // Oldest-first chronological rendering of the 3 newest messages.
        $this->assertSame(['message-2', 'message-3', 'message-4'], $firstBodies);

        $nextCursor = $firstPage->json('meta.next_cursor');
        $this->assertNotNull($nextCursor);

        $secondPage = $this->getJson("/api/v1/conversations/{$conversationId}/messages?limit=3&cursor={$nextCursor}")->assertOk();
        $secondBodies = collect($secondPage->json('data'))->pluck('body')->all();
        $this->assertSame(['message-0', 'message-1'], $secondBodies);
    }
}
