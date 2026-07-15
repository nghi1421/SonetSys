<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Group;

use App\Core\Auth\Domain\Models\User;
use App\Modules\Feed\Domain\Models\Post;
use App\Modules\Group\Domain\Models\Group;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class GroupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    public function test_member_can_create_a_group_and_becomes_its_owner(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/groups', [
            'name' => 'Book Club',
            'description' => 'For readers',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.name', 'Book Club');
        $response->assertJsonPath('data.slug', 'book-club');
        $response->assertJsonPath('data.visibility', 'public');
        $response->assertJsonPath('data.members_count', 1);
        $response->assertJsonPath('data.viewer_membership', null);

        $this->assertDatabaseHas('group_members', [
            'user_id' => $user->id,
            'role' => 'owner',
            'status' => 'approved',
        ]);
    }

    public function test_any_tenant_member_can_join_a_public_group_instantly(): void
    {
        $owner = User::factory()->create();
        $group = $this->createGroup($owner, 'public');

        $joiner = $this->sameTenantUser($owner);
        Sanctum::actingAs($joiner);

        $response = $this->postJson("/api/v1/groups/{$group->id}/join");

        $response->assertOk();
        $response->assertJsonPath('data.status', 'approved');

        $this->assertSame(2, $group->fresh()->members_count);
    }

    public function test_joining_a_private_group_creates_a_pending_request(): void
    {
        $owner = User::factory()->create();
        $group = $this->createGroup($owner, 'private');

        $joiner = $this->sameTenantUser($owner);
        Sanctum::actingAs($joiner);

        $response = $this->postJson("/api/v1/groups/{$group->id}/join");

        $response->assertOk();
        $response->assertJsonPath('data.status', 'pending');

        // Members count only reflects approved membership.
        $this->assertSame(1, $group->fresh()->members_count);
    }

    public function test_owner_can_approve_a_pending_join_request(): void
    {
        $owner = User::factory()->create();
        $group = $this->createGroup($owner, 'private');

        $joiner = $this->sameTenantUser($owner);
        $this->joinAsPending($group, $joiner);

        Sanctum::actingAs($owner);

        $response = $this->postJson("/api/v1/groups/{$group->id}/requests/{$joiner->id}/approve");

        $response->assertOk();
        $response->assertJsonPath('data.status', 'approved');
        $this->assertSame(2, $group->fresh()->members_count);
    }

    public function test_non_owner_cannot_approve_join_requests(): void
    {
        $owner = User::factory()->create();
        $group = $this->createGroup($owner, 'private');

        $joiner = $this->sameTenantUser($owner);
        $this->joinAsPending($group, $joiner);

        $bystander = $this->sameTenantUser($owner);
        Sanctum::actingAs($bystander);

        $response = $this->postJson("/api/v1/groups/{$group->id}/requests/{$joiner->id}/approve");

        $response->assertForbidden();
    }

    public function test_owner_can_remove_an_approved_member(): void
    {
        $owner = User::factory()->create();
        $group = $this->createGroup($owner, 'public');

        $member = $this->sameTenantUser($owner);
        $this->joinAsApproved($group, $member);

        Sanctum::actingAs($owner);

        $response = $this->deleteJson("/api/v1/groups/{$group->id}/members/{$member->id}");

        $response->assertOk();
        $this->assertSame(1, $group->fresh()->members_count);
        $this->assertDatabaseMissing('group_members', ['group_id' => $group->id, 'user_id' => $member->id]);
    }

    public function test_owner_cannot_be_removed_and_cannot_leave(): void
    {
        $owner = User::factory()->create();
        $group = $this->createGroup($owner, 'public');

        Sanctum::actingAs($owner);

        $this->postJson("/api/v1/groups/{$group->id}/leave")->assertForbidden();
        $this->deleteJson("/api/v1/groups/{$group->id}/members/{$owner->id}")->assertForbidden();
    }

    public function test_member_can_leave_a_group(): void
    {
        $owner = User::factory()->create();
        $group = $this->createGroup($owner, 'public');

        $member = $this->sameTenantUser($owner);
        $this->joinAsApproved($group, $member);

        Sanctum::actingAs($member);

        $response = $this->postJson("/api/v1/groups/{$group->id}/leave");

        $response->assertOk();
        $this->assertSame(1, $group->fresh()->members_count);
    }

    public function test_only_owner_can_update_or_delete_the_group(): void
    {
        $owner = User::factory()->create();
        $group = $this->createGroup($owner, 'public');

        $bystander = $this->sameTenantUser($owner);
        Sanctum::actingAs($bystander);

        $this->putJson("/api/v1/groups/{$group->id}", [
            'name' => 'Renamed',
            'visibility' => 'public',
        ])->assertForbidden();

        $this->deleteJson("/api/v1/groups/{$group->id}")->assertForbidden();
    }

    public function test_a_group_is_not_visible_to_users_from_another_tenant(): void
    {
        $owner = User::factory()->create();
        $group = $this->createGroup($owner, 'public');

        $outsider = User::factory()->create();
        Sanctum::actingAs($outsider);

        $this->getJson("/api/v1/groups/{$group->slug}")->assertNotFound();
        $this->postJson("/api/v1/groups/{$group->id}/join")->assertNotFound();
    }

    public function test_member_can_post_in_group_and_it_appears_in_group_feed_only(): void
    {
        $owner = User::factory()->create();
        $group = $this->createGroup($owner, 'public');

        Sanctum::actingAs($owner);

        $this->postJson("/api/v1/groups/{$group->id}/posts", [
            'body' => 'Hello group',
        ])->assertCreated();

        $groupFeed = $this->getJson("/api/v1/groups/{$group->id}/posts");
        $groupFeed->assertOk();
        $this->assertCount(1, $groupFeed->json('data'));
        $groupFeed->assertJsonPath('data.0.group_id', $group->id);

        $mainFeed = $this->getJson('/api/v1/posts');
        $mainFeed->assertOk();
        $this->assertCount(0, $mainFeed->json('data'));

        $this->assertDatabaseCount('posts', 1);
        $this->assertSame($group->id, Post::query()->firstOrFail()->group_id);
    }

    public function test_non_member_cannot_view_or_post_to_a_private_groups_feed(): void
    {
        $owner = User::factory()->create();
        $group = $this->createGroup($owner, 'private');

        $outsider = $this->sameTenantUser($owner);
        Sanctum::actingAs($outsider);

        $this->getJson("/api/v1/groups/{$group->id}/posts")->assertForbidden();
        $this->postJson("/api/v1/groups/{$group->id}/posts", ['body' => 'Sneaky'])->assertForbidden();
    }

    private function sameTenantUser(User $owner): User
    {
        // Reuse the owner's existing role row instead of UserFactory::forTenant(),
        // which would try to create a second 'member' role for the same tenant
        // and collide with the (tenant_id, slug) unique constraint.
        return User::factory()->create([
            'tenant_id' => $owner->tenant_id,
            'role_id' => $owner->role_id,
        ]);
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

    private function joinAsPending(Group $group, User $user): void
    {
        Sanctum::actingAs($user);
        $this->postJson("/api/v1/groups/{$group->id}/join")->assertJsonPath('data.status', 'pending');
    }

    private function joinAsApproved(Group $group, User $user): void
    {
        Sanctum::actingAs($user);
        $this->postJson("/api/v1/groups/{$group->id}/join")->assertJsonPath('data.status', 'approved');
    }
}
