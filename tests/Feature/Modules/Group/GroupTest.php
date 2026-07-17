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
        $response->assertJsonPath('data.viewer_membership.role', 'owner');
        $response->assertJsonPath('data.viewer_membership.status', 'approved');

        $this->assertDatabaseHas('group_members', [
            'user_id' => $user->id,
            'role' => 'owner',
            'status' => 'approved',
        ]);
    }

    public function test_any_registered_user_can_join_a_public_group_instantly(): void
    {
        $owner = User::factory()->create();
        $group = $this->createGroup($owner, 'public');

        $joiner = $this->otherUser();
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

        $joiner = $this->otherUser();
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

        $joiner = $this->otherUser();
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

        $joiner = $this->otherUser();
        $this->joinAsPending($group, $joiner);

        $bystander = $this->otherUser();
        Sanctum::actingAs($bystander);

        $response = $this->postJson("/api/v1/groups/{$group->id}/requests/{$joiner->id}/approve");

        $response->assertForbidden();
    }

    public function test_owner_can_remove_an_approved_member(): void
    {
        $owner = User::factory()->create();
        $group = $this->createGroup($owner, 'public');

        $member = $this->otherUser();
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

        $member = $this->otherUser();
        $this->joinAsApproved($group, $member);

        Sanctum::actingAs($member);

        $response = $this->postJson("/api/v1/groups/{$group->id}/leave");

        $response->assertOk();
        $this->assertSame(1, $group->fresh()->members_count);
    }

    public function test_bystander_cannot_update_or_delete_the_group(): void
    {
        $owner = User::factory()->create();
        $group = $this->createGroup($owner, 'public');

        $bystander = $this->otherUser();
        Sanctum::actingAs($bystander);

        $this->putJson("/api/v1/groups/{$group->id}", [
            'name' => 'Renamed',
            'visibility' => 'public',
        ])->assertForbidden();

        $this->deleteJson("/api/v1/groups/{$group->id}")->assertForbidden();
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

    public function test_non_member_can_view_but_not_post_to_a_public_groups_feed(): void
    {
        $owner = User::factory()->create();
        $group = $this->createGroup($owner, 'public');

        Sanctum::actingAs($owner);
        $this->postJson("/api/v1/groups/{$group->id}/posts", ['body' => 'Hello group'])->assertCreated();

        $outsider = $this->otherUser();
        Sanctum::actingAs($outsider);

        $this->getJson("/api/v1/groups/{$group->id}/posts")->assertOk();
        $this->postJson("/api/v1/groups/{$group->id}/posts", ['body' => 'Sneaky'])->assertForbidden();
    }

    public function test_non_member_cannot_view_or_post_to_a_private_groups_feed(): void
    {
        $owner = User::factory()->create();
        $group = $this->createGroup($owner, 'private');

        $outsider = $this->otherUser();
        Sanctum::actingAs($outsider);

        $this->getJson("/api/v1/groups/{$group->id}/posts")->assertForbidden();
        $this->postJson("/api/v1/groups/{$group->id}/posts", ['body' => 'Sneaky'])->assertForbidden();
    }

    public function test_non_member_cannot_reach_a_private_groups_post_via_the_generic_feed_endpoints(): void
    {
        $owner = User::factory()->create();
        $group = $this->createGroup($owner, 'private');

        Sanctum::actingAs($owner);
        $postId = $this->postJson("/api/v1/groups/{$group->id}/posts", ['body' => 'Members only'])
            ->assertCreated()
            ->json('data.id');

        $outsider = $this->otherUser();
        Sanctum::actingAs($outsider);

        $this->getJson("/api/v1/posts/{$postId}")->assertForbidden();
        $this->getJson("/api/v1/posts/{$postId}/comments")->assertForbidden();
        $this->postJson("/api/v1/posts/{$postId}/comments", ['body' => 'Sneaky comment'])->assertForbidden();
        $this->postJson("/api/v1/posts/{$postId}/like")->assertForbidden();
    }

    public function test_any_registered_user_can_reach_a_public_groups_post_via_the_generic_feed_endpoints(): void
    {
        $owner = User::factory()->create();
        $group = $this->createGroup($owner, 'public');

        Sanctum::actingAs($owner);
        $postId = $this->postJson("/api/v1/groups/{$group->id}/posts", ['body' => 'Public post'])
            ->assertCreated()
            ->json('data.id');

        $outsider = $this->otherUser();
        Sanctum::actingAs($outsider);

        $this->getJson("/api/v1/posts/{$postId}")->assertOk();
        $this->getJson("/api/v1/posts/{$postId}/comments")->assertOk();

        // Viewing is open to any registered user, but interacting still requires membership.
        $this->postJson("/api/v1/posts/{$postId}/comments", ['body' => 'Sneaky comment'])->assertForbidden();
        $this->postJson("/api/v1/posts/{$postId}/like")->assertForbidden();
    }

    public function test_owner_can_promote_a_member_to_admin(): void
    {
        $owner = User::factory()->create();
        $group = $this->createGroup($owner, 'public');

        $member = $this->otherUser();
        $this->joinAsApproved($group, $member);

        Sanctum::actingAs($owner);

        $response = $this->postJson("/api/v1/groups/{$group->id}/members/{$member->id}/promote");

        $response->assertOk();
        $response->assertJsonPath('data.role', 'admin');
    }

    public function test_non_owner_cannot_promote_a_member_to_admin(): void
    {
        $owner = User::factory()->create();
        $group = $this->createGroup($owner, 'public');

        $admin = $this->otherUser();
        $this->joinAsApproved($group, $admin);
        $this->promoteToAdmin($group, $owner, $admin);

        $member = $this->otherUser();
        $this->joinAsApproved($group, $member);

        Sanctum::actingAs($admin);

        $this->postJson("/api/v1/groups/{$group->id}/members/{$member->id}/promote")->assertForbidden();
    }

    public function test_owner_can_demote_an_admin_back_to_member(): void
    {
        $owner = User::factory()->create();
        $group = $this->createGroup($owner, 'public');

        $admin = $this->otherUser();
        $this->joinAsApproved($group, $admin);
        $this->promoteToAdmin($group, $owner, $admin);

        Sanctum::actingAs($owner);

        $response = $this->postJson("/api/v1/groups/{$group->id}/members/{$admin->id}/demote");

        $response->assertOk();
        $response->assertJsonPath('data.role', 'member');
    }

    public function test_admin_can_approve_join_requests_and_remove_members(): void
    {
        $owner = User::factory()->create();
        $group = $this->createGroup($owner, 'private');

        $admin = $this->otherUser();
        $this->joinAsPending($group, $admin);
        Sanctum::actingAs($owner);
        $this->postJson("/api/v1/groups/{$group->id}/requests/{$admin->id}/approve")->assertOk();
        $this->promoteToAdmin($group, $owner, $admin);

        $joiner = $this->otherUser();
        $this->joinAsPending($group, $joiner);

        Sanctum::actingAs($admin);

        $this->postJson("/api/v1/groups/{$group->id}/requests/{$joiner->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->deleteJson("/api/v1/groups/{$group->id}/members/{$joiner->id}")->assertOk();
    }

    public function test_admin_can_update_group_settings_but_cannot_delete_the_group(): void
    {
        $owner = User::factory()->create();
        $group = $this->createGroup($owner, 'public');

        $admin = $this->otherUser();
        $this->joinAsApproved($group, $admin);
        $this->promoteToAdmin($group, $owner, $admin);

        Sanctum::actingAs($admin);

        $this->putJson("/api/v1/groups/{$group->id}", [
            'name' => 'Renamed by admin',
            'visibility' => 'public',
        ])->assertOk();

        $this->deleteJson("/api/v1/groups/{$group->id}")->assertForbidden();
    }

    public function test_admin_cannot_remove_another_admin(): void
    {
        $owner = User::factory()->create();
        $group = $this->createGroup($owner, 'public');

        $adminOne = $this->otherUser();
        $this->joinAsApproved($group, $adminOne);
        $this->promoteToAdmin($group, $owner, $adminOne);

        $adminTwo = $this->otherUser();
        $this->joinAsApproved($group, $adminTwo);
        $this->promoteToAdmin($group, $owner, $adminTwo);

        Sanctum::actingAs($adminOne);

        $this->deleteJson("/api/v1/groups/{$group->id}/members/{$adminTwo->id}")->assertForbidden();
    }

    private function otherUser(): User
    {
        return User::factory()->create();
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

    private function promoteToAdmin(Group $group, User $owner, User $member): void
    {
        Sanctum::actingAs($owner);
        $this->postJson("/api/v1/groups/{$group->id}/members/{$member->id}/promote")
            ->assertJsonPath('data.role', 'admin');
    }
}
