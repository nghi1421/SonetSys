<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Report;

use App\Core\Auth\Domain\Models\User;
use App\Modules\Feed\Domain\Models\Comment;
use App\Modules\Feed\Domain\Models\Post;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class ReportTest extends TestCase
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

    private function createComment(User $author, Post $post, array $overrides = []): Comment
    {
        return Comment::query()->create([
            'post_id' => $post->id,
            'author_id' => $author->id,
            'body' => 'A comment',
            ...$overrides,
        ]);
    }

    public function test_reporting_a_post_creates_a_pending_report(): void
    {
        $author = User::factory()->create();
        $post = $this->createPost($author);

        $reporter = User::factory()->create();
        Sanctum::actingAs($reporter);

        $response = $this->postJson("/api/v1/posts/{$post->id}/report", [
            'reason' => 'spam',
            'details' => 'Looks like spam',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.status', 'pending');
        $response->assertJsonPath('data.reason', 'spam');

        $this->assertDatabaseHas('reports', [
            'reportable_type' => 'post',
            'reportable_id' => $post->id,
            'reporter_id' => $reporter->id,
            'reason' => 'spam',
            'status' => 'pending',
        ]);
    }

    public function test_reporting_a_comment_creates_a_pending_report(): void
    {
        $author = User::factory()->create();
        $post = $this->createPost($author);
        $comment = $this->createComment($author, $post);

        $reporter = User::factory()->create();
        Sanctum::actingAs($reporter);

        $response = $this->postJson("/api/v1/comments/{$comment->id}/report", [
            'reason' => 'harassment',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('reports', [
            'reportable_type' => 'comment',
            'reportable_id' => $comment->id,
            'reporter_id' => $reporter->id,
            'reason' => 'harassment',
            'status' => 'pending',
        ]);
    }

    public function test_reporting_your_own_post_is_rejected(): void
    {
        $author = User::factory()->create();
        $post = $this->createPost($author);

        Sanctum::actingAs($author);

        $this->postJson("/api/v1/posts/{$post->id}/report", ['reason' => 'spam'])
            ->assertStatus(422);

        $this->assertDatabaseMissing('reports', ['reportable_type' => 'post', 'reportable_id' => $post->id]);
    }

    public function test_reporting_your_own_comment_is_rejected(): void
    {
        $author = User::factory()->create();
        $post = $this->createPost($author);
        $comment = $this->createComment($author, $post);

        Sanctum::actingAs($author);

        $this->postJson("/api/v1/comments/{$comment->id}/report", ['reason' => 'spam'])
            ->assertStatus(422);

        $this->assertDatabaseMissing('reports', ['reportable_type' => 'comment', 'reportable_id' => $comment->id]);
    }

    public function test_a_duplicate_report_from_the_same_user_on_the_same_target_is_rejected(): void
    {
        $author = User::factory()->create();
        $post = $this->createPost($author);

        $reporter = User::factory()->create();
        Sanctum::actingAs($reporter);

        $this->postJson("/api/v1/posts/{$post->id}/report", ['reason' => 'spam'])->assertCreated();
        $this->postJson("/api/v1/posts/{$post->id}/report", ['reason' => 'other'])->assertStatus(422);

        $this->assertDatabaseCount('reports', 1);
    }

    public function test_a_non_admin_non_moderator_gets_403_on_the_admin_reports_queue_and_actions(): void
    {
        $author = User::factory()->create();
        $post = $this->createPost($author);

        $reporter = User::factory()->create();
        Sanctum::actingAs($reporter);
        $reportId = $this->postJson("/api/v1/posts/{$post->id}/report", ['reason' => 'spam'])
            ->assertCreated()
            ->json('data.id');

        $regularUser = User::factory()->create();
        Sanctum::actingAs($regularUser);

        $this->getJson('/api/v1/admin/reports')->assertForbidden();
        $this->postJson("/api/v1/admin/reports/{$reportId}/resolve")->assertForbidden();
        $this->postJson("/api/v1/admin/reports/{$reportId}/dismiss")->assertForbidden();
    }

    public function test_a_moderator_not_just_an_admin_can_access_the_reports_queue(): void
    {
        $author = User::factory()->create();
        $post = $this->createPost($author);

        $reporter = User::factory()->create();
        Sanctum::actingAs($reporter);
        $this->postJson("/api/v1/posts/{$post->id}/report", ['reason' => 'spam'])->assertCreated();

        $moderator = User::factory()->moderator()->create();
        Sanctum::actingAs($moderator);

        $this->getJson('/api/v1/admin/reports')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_a_moderator_resolving_a_report_soft_deletes_the_underlying_post(): void
    {
        $author = User::factory()->create();
        $post = $this->createPost($author);

        $reporter = User::factory()->create();
        Sanctum::actingAs($reporter);
        $reportId = $this->postJson("/api/v1/posts/{$post->id}/report", ['reason' => 'spam'])
            ->assertCreated()
            ->json('data.id');

        $moderator = User::factory()->moderator()->create();
        Sanctum::actingAs($moderator);

        $this->postJson("/api/v1/admin/reports/{$reportId}/resolve")
            ->assertOk()
            ->assertJsonPath('data.status', 'resolved');

        $this->assertDatabaseHas('reports', ['id' => $reportId, 'status' => 'resolved']);
        $this->assertSoftDeleted('posts', ['id' => $post->id]);
    }

    public function test_a_moderator_dismissing_a_report_marks_it_dismissed_without_touching_the_content(): void
    {
        $author = User::factory()->create();
        $post = $this->createPost($author);

        $reporter = User::factory()->create();
        Sanctum::actingAs($reporter);
        $reportId = $this->postJson("/api/v1/posts/{$post->id}/report", ['reason' => 'spam'])
            ->assertCreated()
            ->json('data.id');

        $moderator = User::factory()->moderator()->create();
        Sanctum::actingAs($moderator);

        $this->postJson("/api/v1/admin/reports/{$reportId}/dismiss")
            ->assertOk()
            ->assertJsonPath('data.status', 'dismissed');

        $this->assertDatabaseHas('reports', ['id' => $reportId, 'status' => 'dismissed']);
        $this->assertDatabaseHas('posts', ['id' => $post->id, 'deleted_at' => null]);
    }

    public function test_an_admin_resolving_a_report_soft_deletes_the_underlying_comment(): void
    {
        $author = User::factory()->create();
        $post = $this->createPost($author);
        $comment = $this->createComment($author, $post);

        $reporter = User::factory()->create();
        Sanctum::actingAs($reporter);
        $reportId = $this->postJson("/api/v1/comments/{$comment->id}/report", ['reason' => 'inappropriate'])
            ->assertCreated()
            ->json('data.id');

        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $this->postJson("/api/v1/admin/reports/{$reportId}/resolve")
            ->assertOk()
            ->assertJsonPath('data.status', 'resolved');

        $this->assertSoftDeleted('comments', ['id' => $comment->id]);
    }
}
