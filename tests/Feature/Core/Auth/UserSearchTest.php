<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Auth;

use App\Core\Auth\Domain\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class UserSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_searching_returns_users_whose_name_matches_the_query(): void
    {
        Sanctum::actingAs(User::factory()->create());
        User::factory()->create(['name' => 'Alice Nguyen']);
        User::factory()->create(['name' => 'Bob Tran']);

        $response = $this->getJson('/api/v1/users/search?q=alice');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Alice Nguyen');
    }

    public function test_search_results_are_capped_at_ten(): void
    {
        Sanctum::actingAs(User::factory()->create());
        User::factory()->count(15)->create(['name' => fn () => 'Matching User '.fake()->unique()->numberBetween(1, 10000)]);

        $response = $this->getJson('/api/v1/users/search?q=Matching');

        $response->assertOk();
        $this->assertCount(10, $response->json('data'));
    }

    public function test_a_query_shorter_than_two_characters_is_rejected(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/users/search?q=a')->assertUnprocessable();
    }

    public function test_a_non_matching_query_returns_an_empty_list(): void
    {
        Sanctum::actingAs(User::factory()->create());
        User::factory()->create(['name' => 'Someone Else']);

        $response = $this->getJson('/api/v1/users/search?q=zzzznomatch');

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
    }
}
