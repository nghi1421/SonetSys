<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Search;

use App\Core\Auth\Domain\Models\User;
use App\Modules\Group\Domain\Models\Group;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class SearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    public function test_searching_returns_matching_posts_users_groups_and_hashtags(): void
    {
        $author = User::factory()->create(['name' => 'Alice Wanderlust']);
        Sanctum::actingAs($author);

        $this->postJson('/api/v1/posts', [
            'body' => 'Just booked a trip, so much wanderlust right now',
            'visibility' => 'public',
        ])->assertCreated();

        $this->postJson('/api/v1/posts', [
            'body' => 'Chasing #wanderlust across the globe',
            'visibility' => 'public',
        ])->assertCreated();

        $this->postJson('/api/v1/groups', ['name' => 'Wanderlust Travelers'])->assertCreated();

        $viewer = User::factory()->create();
        Sanctum::actingAs($viewer);

        $response = $this->getJson('/api/v1/search?q=wanderlust');

        $response->assertOk();
        $data = $response->json('data');

        $this->assertCount(2, $data['posts']);
        $this->assertCount(1, $data['users']);
        $this->assertSame('Alice Wanderlust', $data['users'][0]['name']);
        $this->assertCount(1, $data['groups']);
        $this->assertSame('Wanderlust Travelers', $data['groups'][0]['name']);
        $this->assertContains('wanderlust', $data['hashtags']);
    }

    public function test_a_private_post_never_appears_in_search_results_even_when_its_body_matches(): void
    {
        $author = User::factory()->create();
        Sanctum::actingAs($author);

        $this->postJson('/api/v1/posts', [
            'body' => 'This secret plan mentions unicornsecret42 nowhere else',
            'visibility' => 'private',
        ])->assertCreated();

        $viewer = User::factory()->create();
        Sanctum::actingAs($viewer);

        $response = $this->getJson('/api/v1/search?q=unicornsecret42');

        $response->assertOk();
        $this->assertCount(0, $response->json('data.posts'));
    }

    public function test_a_group_posts_body_never_appears_in_search_results_even_when_it_matches(): void
    {
        $owner = User::factory()->create();
        Sanctum::actingAs($owner);

        $group = Group::query()->findOrFail(
            $this->postJson('/api/v1/groups', ['name' => 'Secret Society'])->assertCreated()->json('data.id'),
        );

        $this->postJson("/api/v1/groups/{$group->id}/posts", [
            'body' => 'Only group members should ever see groupsecret99',
        ])->assertCreated();

        $viewer = User::factory()->create();
        Sanctum::actingAs($viewer);

        $response = $this->getJson('/api/v1/search?q=groupsecret99');

        $response->assertOk();
        $this->assertCount(0, $response->json('data.posts'));
    }

    public function test_a_members_visibility_post_never_appears_in_search_results_even_for_its_own_author(): void
    {
        $author = User::factory()->create();
        Sanctum::actingAs($author);

        $this->postJson('/api/v1/posts', [
            'body' => 'Members only chatter about membersonlyterm77',
            'visibility' => 'members',
        ])->assertCreated();

        $response = $this->getJson('/api/v1/search?q=membersonlyterm77');

        $response->assertOk();
        $this->assertCount(0, $response->json('data.posts'));
    }

    public function test_a_non_matching_query_returns_empty_arrays_for_all_categories_not_an_error(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson('/api/v1/search?q=zzznomatchanywhere');

        $response->assertOk();
        $data = $response->json('data');

        $this->assertSame([], $data['posts']);
        $this->assertSame([], $data['users']);
        $this->assertSame([], $data['groups']);
        $this->assertSame([], $data['hashtags']);
    }

    public function test_a_query_shorter_than_two_characters_is_rejected(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/search?q=a')->assertUnprocessable();
    }

    public function test_a_private_group_never_appears_in_search_results_even_when_its_name_matches(): void
    {
        $owner = User::factory()->create();
        Sanctum::actingAs($owner);

        $this->postJson('/api/v1/groups', [
            'name' => 'Hidden Clubhouse',
            'visibility' => 'private',
        ])->assertCreated();

        $viewer = User::factory()->create();
        Sanctum::actingAs($viewer);

        $response = $this->getJson('/api/v1/search?q=clubhouse');

        $response->assertOk();
        $this->assertCount(0, $response->json('data.groups'));
    }
}
