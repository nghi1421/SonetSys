<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Group;

use App\Core\Auth\Domain\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class GroupCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_newly_created_group_appears_in_the_tenant_listing_even_after_it_was_cached(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Populate the cache with an empty listing.
        $this->getJson('/api/v1/groups')->assertOk()->assertJsonCount(0, 'data');

        $this->postJson('/api/v1/groups', ['name' => 'Book Club'])->assertCreated();

        $this->getJson('/api/v1/groups')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_members_count_in_the_tenant_listing_updates_even_after_it_was_cached(): void
    {
        $owner = User::factory()->create();
        Sanctum::actingAs($owner);
        $groupId = $this->postJson('/api/v1/groups', ['name' => 'Book Club'])
            ->assertCreated()
            ->json('data.id');

        // Populate the cache with members_count = 1 (owner only).
        $this->getJson('/api/v1/groups')->assertJsonPath('data.0.members_count', 1);

        $joiner = User::factory()->create([
            'tenant_id' => $owner->tenant_id,
            'role_id' => $owner->role_id,
        ]);
        Sanctum::actingAs($joiner);
        $this->postJson("/api/v1/groups/{$groupId}/join")->assertOk();

        $this->getJson('/api/v1/groups')->assertJsonPath('data.0.members_count', 2);
    }

    public function test_deleting_a_group_removes_it_from_the_tenant_listing_even_after_it_was_cached(): void
    {
        $owner = User::factory()->create();
        Sanctum::actingAs($owner);
        $groupId = $this->postJson('/api/v1/groups', ['name' => 'Book Club'])
            ->assertCreated()
            ->json('data.id');

        $this->getJson('/api/v1/groups')->assertJsonCount(1, 'data');

        $this->deleteJson("/api/v1/groups/{$groupId}")->assertOk();

        $this->getJson('/api/v1/groups')->assertJsonCount(0, 'data');
    }
}
