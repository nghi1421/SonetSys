<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Story;

use App\Core\Auth\Domain\Models\User;
use App\Modules\Story\Domain\Models\Story;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class StoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        Storage::fake('public');
    }

    private function createStory(User $author, array $overrides = []): Story
    {
        return Story::query()->create([
            'author_id' => $author->id,
            'media_type' => 'image',
            'media_disk' => 'public',
            'media_path' => 'stories/fake.jpg',
            'caption' => null,
            'published_at' => now(),
            'expires_at' => now()->addHours(24),
            ...$overrides,
        ]);
    }

    public function test_creating_a_story_without_media_fails_validation(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/stories', [
            'media_type' => 'image',
        ])->assertStatus(422);
    }

    public function test_creating_a_story_sets_expiry_24_hours_after_publish(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $storyId = $this->postJson('/api/v1/stories', [
            'media' => UploadedFile::fake()->image('photo.jpg'),
            'media_type' => 'image',
        ])->assertCreated()->json('data.id');

        $story = Story::query()->findOrFail($storyId);
        $this->assertSame(
            $story->published_at->addHours(24)->timestamp,
            $story->expires_at->timestamp,
        );
    }

    public function test_active_feed_excludes_an_expired_story(): void
    {
        $author = User::factory()->create();
        $this->createStory($author, ['expires_at' => now()->subHour()]);
        $active = $this->createStory($author, ['expires_at' => now()->addHours(12)]);

        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson('/api/v1/stories')->assertOk();
        $storyIds = collect($response->json('data'))
            ->flatMap(fn (array $group) => collect($group['stories'])->pluck('id'));

        $this->assertTrue($storyIds->contains($active->id));
        $this->assertCount(1, $storyIds);
    }

    public function test_active_feed_orders_authors_with_unviewed_stories_first(): void
    {
        $viewer = User::factory()->create();

        $olderUnviewedAuthor = User::factory()->create();
        $olderUnviewedStory = $this->createStory($olderUnviewedAuthor, ['published_at' => now()->subHours(2)]);

        $newerViewedAuthor = User::factory()->create();
        $newerViewedStory = $this->createStory($newerViewedAuthor, ['published_at' => now()->subMinutes(5)]);

        Sanctum::actingAs($viewer);
        $this->postJson("/api/v1/stories/{$newerViewedStory->id}/view")->assertOk();

        $response = $this->getJson('/api/v1/stories')->assertOk();
        $authorIds = collect($response->json('data'))->pluck('author.id');

        $this->assertSame(
            [$olderUnviewedAuthor->id, $newerViewedAuthor->id],
            $authorIds->all(),
        );
    }

    public function test_owner_can_delete_their_own_story(): void
    {
        $author = User::factory()->create();
        $story = $this->createStory($author);
        Sanctum::actingAs($author);

        $this->deleteJson("/api/v1/stories/{$story->id}")->assertOk();

        $this->assertDatabaseMissing('stories', ['id' => $story->id]);
    }

    public function test_moderator_can_delete_any_story(): void
    {
        $story = $this->createStory(User::factory()->create());
        Sanctum::actingAs(User::factory()->moderator()->create());

        $this->deleteJson("/api/v1/stories/{$story->id}")->assertOk();

        $this->assertDatabaseMissing('stories', ['id' => $story->id]);
    }

    public function test_a_plain_user_cannot_delete_another_users_story(): void
    {
        $story = $this->createStory(User::factory()->create());
        Sanctum::actingAs(User::factory()->create());

        $this->deleteJson("/api/v1/stories/{$story->id}")->assertForbidden();

        $this->assertDatabaseHas('stories', ['id' => $story->id]);
    }

    public function test_viewing_a_story_twice_records_a_single_view(): void
    {
        $story = $this->createStory(User::factory()->create());
        $viewer = User::factory()->create();
        Sanctum::actingAs($viewer);

        $this->postJson("/api/v1/stories/{$story->id}/view")->assertOk();
        $this->postJson("/api/v1/stories/{$story->id}/view")->assertOk();

        $this->assertDatabaseCount('story_views', 1);
    }

    public function test_owner_can_list_viewers(): void
    {
        $author = User::factory()->create();
        $story = $this->createStory($author);
        $viewer = User::factory()->create();
        Sanctum::actingAs($viewer);
        $this->postJson("/api/v1/stories/{$story->id}/view")->assertOk();

        Sanctum::actingAs($author);
        $this->getJson("/api/v1/stories/{$story->id}/viewers")
            ->assertOk()
            ->assertJsonPath('data.0.viewer.id', $viewer->id);
    }

    public function test_a_non_owner_cannot_list_viewers(): void
    {
        $story = $this->createStory(User::factory()->create());
        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/v1/stories/{$story->id}/viewers")->assertForbidden();
    }
}
