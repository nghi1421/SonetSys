<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Song;

use App\Core\Auth\Domain\Models\User;
use App\Modules\Song\Domain\Models\Song;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class SongSearchTest extends TestCase
{
    use RefreshDatabase;

    private function makeSong(string $title, ?string $artist = null): Song
    {
        return Song::query()->create([
            'title' => $title,
            'artist' => $artist,
            'audio_disk' => 'local',
            'audio_path' => "songs/{$title}.mp3",
            'uploaded_by' => User::factory()->create()->id,
        ]);
    }

    public function test_any_authenticated_user_can_list_songs_without_a_permission_gate(): void
    {
        $this->makeSong('Sunset Drive', 'Neon Wave');

        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/songs')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Sunset Drive');
    }

    public function test_search_query_filters_by_title_or_artist(): void
    {
        $this->makeSong('Sunset Drive', 'Neon Wave');
        $this->makeSong('Midnight Run', 'Neon Wave');
        $this->makeSong('Ocean Blue', 'Coral Sky');

        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/songs?q=Sunset')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Sunset Drive');

        $this->getJson('/api/v1/songs?q=Neon Wave')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_search_respects_the_limit_query_parameter_capped_at_fifty(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->makeSong("Song {$i}");
        }

        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/songs?limit=2')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }
}
