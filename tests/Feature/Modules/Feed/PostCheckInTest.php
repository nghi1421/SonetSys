<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Feed;

use App\Core\Auth\Domain\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class PostCheckInTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_post_with_a_full_valid_location_persists_and_returns_it(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/posts', [
            'body' => 'Having a great time!',
            'location_name' => 'Hanoi, Vietnam',
            'location_lat' => 21.0278,
            'location_lng' => 105.8342,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.location.name', 'Hanoi, Vietnam');
        $response->assertJsonPath('data.location.lat', 21.0278);
        $response->assertJsonPath('data.location.lng', 105.8342);

        $this->assertDatabaseHas('posts', [
            'id' => $response->json('data.id'),
            'location_name' => 'Hanoi, Vietnam',
        ]);
    }

    public function test_a_location_only_post_with_no_body_or_media_succeeds(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/posts', [
            'location_name' => 'Paris, France',
            'location_lat' => 48.8566,
            'location_lng' => 2.3522,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.location.name', 'Paris, France');
    }

    public function test_a_partial_location_is_rejected(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/posts', [
            'body' => 'Missing coordinates',
            'location_name' => 'Somewhere',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonPath('meta.errors.location_name.0', fn ($m) => is_string($m));
    }

    public function test_out_of_range_latitude_is_rejected(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/posts', [
            'body' => 'Bad coordinates',
            'location_name' => 'Nowhere',
            'location_lat' => 999,
            'location_lng' => 105.8342,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonPath('meta.errors.location_lat.0', fn ($m) => is_string($m));
    }

    public function test_out_of_range_longitude_is_rejected(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/posts', [
            'body' => 'Bad coordinates',
            'location_name' => 'Nowhere',
            'location_lat' => 21.0278,
            'location_lng' => 999,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonPath('meta.errors.location_lng.0', fn ($m) => is_string($m));
    }

    public function test_a_post_with_no_location_returns_null_location(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/posts', [
            'body' => 'Just a regular post',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.location', null);
    }
}
