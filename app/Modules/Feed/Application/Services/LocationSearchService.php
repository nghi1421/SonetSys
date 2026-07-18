<?php

declare(strict_types=1);

namespace App\Modules\Feed\Application\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

final class LocationSearchService
{
    private const CACHE_TTL_SECONDS = 3600;

    /**
     * @return array<int, array{name: string, lat: float, lng: float}>
     */
    public function search(string $query): array
    {
        $normalizedQuery = mb_strtolower(trim($query));

        return Cache::remember(
            "nominatim:{$normalizedQuery}",
            self::CACHE_TTL_SECONDS,
            fn () => $this->fetch($normalizedQuery),
        );
    }

    /**
     * @return array<int, array{name: string, lat: float, lng: float}>
     */
    private function fetch(string $query): array
    {
        $response = Http::withHeaders([
            'User-Agent' => config('services.nominatim.user_agent'),
        ])->get('https://nominatim.openstreetmap.org/search', [
            'q' => $query,
            'format' => 'jsonv2',
            'limit' => 5,
        ]);

        if ($response->failed()) {
            return [];
        }

        return collect($response->json())
            ->map(fn (array $place) => [
                'name' => (string) $place['display_name'],
                'lat' => (float) $place['lat'],
                'lng' => (float) $place['lon'],
            ])
            ->all();
    }
}
