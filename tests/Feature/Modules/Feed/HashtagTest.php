<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Feed;

use App\Core\Auth\Domain\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class HashtagTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_post_with_a_hashtag_attaches_it_and_returns_it_in_the_resource(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/posts', [
            'body' => 'Loving this #Travel adventure!',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.hashtags', ['travel']);

        $this->assertDatabaseHas('hashtags', ['tag' => 'travel']);
        $this->assertDatabaseCount('hashtags', 1);
    }

    public function test_the_same_hashtag_used_across_two_posts_reuses_one_hashtag_row(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/posts', ['body' => 'First #travel post'])->assertCreated();
        $this->postJson('/api/v1/posts', ['body' => 'Second #TRAVEL post'])->assertCreated();

        $this->assertDatabaseCount('hashtags', 1);
        $this->assertDatabaseHas('hashtags', ['tag' => 'travel']);
    }

    public function test_multiple_distinct_hashtags_in_one_post_are_all_attached(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/posts', [
            'body' => 'So much #fun in the #sun today',
        ]);

        $response->assertCreated();
        $this->assertEqualsCanonicalizing(['fun', 'sun'], $response->json('data.hashtags'));
    }

    public function test_editing_a_posts_body_to_remove_a_hashtag_detaches_it(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $postId = $this->postJson('/api/v1/posts', ['body' => 'Off to #paris this weekend'])
            ->assertCreated()
            ->json('data.id');

        $this->putJson("/api/v1/posts/{$postId}", ['body' => 'Trip got cancelled, staying home'])
            ->assertOk()
            ->assertJsonPath('data.hashtags', []);

        $this->assertDatabaseMissing('hashtaggables', [
            'hashtaggable_type' => 'post',
            'hashtaggable_id' => $postId,
        ]);
        // The hashtag row itself is never deleted, only detached — another
        // post may still reference it.
        $this->assertDatabaseHas('hashtags', ['tag' => 'paris']);
    }

    public function test_editing_a_post_to_swap_one_hashtag_for_another_detaches_the_old_and_attaches_the_new(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $postId = $this->postJson('/api/v1/posts', ['body' => 'Team #alpha wins'])
            ->assertCreated()
            ->json('data.id');

        $this->putJson("/api/v1/posts/{$postId}", ['body' => 'Team #beta wins now'])
            ->assertOk()
            ->assertJsonPath('data.hashtags', ['beta']);
    }

    public function test_a_post_with_no_hashtags_returns_an_empty_array(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/posts', ['body' => 'Just a regular update, no tags here']);

        $response->assertCreated();
        $response->assertJsonPath('data.hashtags', []);
    }

    public function test_a_comment_with_a_hashtag_attaches_it(): void
    {
        $author = User::factory()->create();
        Sanctum::actingAs($author);
        $postId = $this->postJson('/api/v1/posts', ['body' => 'Hello world'])
            ->assertCreated()
            ->json('data.id');

        $response = $this->postJson("/api/v1/posts/{$postId}/comments", [
            'body' => 'Great to see #teamwork here',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.hashtags', ['teamwork']);
    }

    public function test_the_hashtag_feed_endpoint_returns_matching_public_posts_only(): void
    {
        $author = User::factory()->create();
        Sanctum::actingAs($author);

        $this->postJson('/api/v1/posts', ['body' => 'Public #golang post', 'visibility' => 'public'])->assertCreated();
        $this->postJson('/api/v1/posts', ['body' => 'Private #golang post', 'visibility' => 'private'])->assertCreated();
        $this->postJson('/api/v1/posts', ['body' => 'Unrelated post'])->assertCreated();

        $viewer = User::factory()->create();
        Sanctum::actingAs($viewer);

        $response = $this->getJson('/api/v1/hashtags/golang/posts');

        $response->assertOk();
        $bodies = collect($response->json('data'))->pluck('body')->all();

        $this->assertContains('Public #golang post', $bodies);
        $this->assertNotContains('Private #golang post', $bodies);
        $this->assertNotContains('Unrelated post', $bodies);
    }

    public function test_the_hashtag_feed_endpoint_no_longer_returns_a_post_after_its_hashtag_is_edited_out(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $postId = $this->postJson('/api/v1/posts', ['body' => 'Exploring #kotlin today', 'visibility' => 'public'])
            ->assertCreated()
            ->json('data.id');

        $this->getJson('/api/v1/hashtags/kotlin/posts')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->putJson("/api/v1/posts/{$postId}", ['body' => 'No more of that topic'])->assertOk();

        $this->getJson('/api/v1/hashtags/kotlin/posts')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
