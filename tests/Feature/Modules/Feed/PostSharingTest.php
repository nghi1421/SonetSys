<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Feed;

use App\Core\Auth\Domain\Models\User;
use App\Modules\Group\Domain\Models\Group;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class PostSharingTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_cannot_share_another_users_private_post(): void
    {
        $author = User::factory()->create();
        Sanctum::actingAs($author);
        $privatePostId = $this->postJson('/api/v1/posts', ['body' => 'Just for me', 'visibility' => 'private'])
            ->assertCreated()
            ->json('data.id');

        $sharer = User::factory()->create();
        Sanctum::actingAs($sharer);

        $this->postJson('/api/v1/posts', ['shared_post_id' => $privatePostId])
            ->assertUnprocessable()
            ->assertJsonPath('meta.errors.shared_post_id.0', fn ($m) => is_string($m));
    }

    public function test_a_non_member_cannot_share_a_post_from_a_private_group(): void
    {
        $owner = User::factory()->create();
        $group = $this->createGroup($owner, 'private');

        Sanctum::actingAs($owner);
        $groupPostId = $this->postJson("/api/v1/groups/{$group->id}/posts", ['body' => 'Members only'])
            ->assertCreated()
            ->json('data.id');

        $outsider = User::factory()->create();
        Sanctum::actingAs($outsider);

        $this->postJson('/api/v1/posts', ['shared_post_id' => $groupPostId])
            ->assertUnprocessable()
            ->assertJsonPath('meta.errors.shared_post_id.0', fn ($m) => is_string($m));
    }

    public function test_any_registered_user_can_share_a_post_from_a_public_group(): void
    {
        $owner = User::factory()->create();
        $group = $this->createGroup($owner, 'public');

        Sanctum::actingAs($owner);
        $groupPostId = $this->postJson("/api/v1/groups/{$group->id}/posts", ['body' => 'Public post'])
            ->assertCreated()
            ->json('data.id');

        $sharer = User::factory()->create();
        Sanctum::actingAs($sharer);

        $this->postJson('/api/v1/posts', ['shared_post_id' => $groupPostId])
            ->assertCreated()
            ->assertJsonPath('data.shared_post.id', $groupPostId);
    }

    private function createGroup(User $owner, string $visibility): Group
    {
        Sanctum::actingAs($owner);

        $response = $this->postJson('/api/v1/groups', [
            'name' => 'Test Group '.uniqid(),
            'visibility' => $visibility,
        ]);

        $response->assertCreated();

        return Group::query()->findOrFail($response->json('data.id'));
    }
}
