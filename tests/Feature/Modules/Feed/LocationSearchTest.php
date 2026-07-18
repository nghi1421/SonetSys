<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Feed;

use App\Core\Auth\Domain\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class LocationSearchTest extends TestCase
{
    use RefreshDatabase;

    private const FAKE_NOMINATIM_RESPONSE = [
        [
            'display_name' => 'Hanoi, Vietnam',
            'lat' => '21.0277644',
            'lon' => '105.8341598',
        ],
        [
            'display_name' => 'Hanoi Old Quarter, Hoan Kiem, Hanoi, Vietnam',
            'lat' => '21.0333',
            'lon' => '105.85',
        ],
    ];

    public function test_it_maps_nominatim_response_to_the_expected_shape(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response(self::FAKE_NOMINATIM_RESPONSE, 200),
        ]);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/locations/search?q=Hanoi');

        $response->assertOk();
        $response->assertJsonPath('data.0.name', 'Hanoi, Vietnam');
        $response->assertJsonPath('data.0.lat', 21.0277644);
        $response->assertJsonPath('data.0.lng', 105.8341598);
        $response->assertJsonCount(2, 'data');
    }

    public function test_a_missing_query_is_rejected(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/locations/search');

        $response->assertUnprocessable();
    }

    public function test_a_too_short_query_is_rejected(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/locations/search?q=a');

        $response->assertUnprocessable();
    }

    public function test_repeated_identical_queries_are_cached_and_only_hit_nominatim_once(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response(self::FAKE_NOMINATIM_RESPONSE, 200),
        ]);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/locations/search?q=Hanoi')->assertOk();
        $this->getJson('/api/v1/locations/search?q=Hanoi')->assertOk();
        $this->getJson('/api/v1/locations/search?q=hanoi')->assertOk();

        Http::assertSentCount(1);
    }
}
