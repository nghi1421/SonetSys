<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Feed;

use App\Core\Auth\Domain\Models\User;
use App\Modules\Feed\Domain\Models\Post;
use App\Modules\Song\Domain\Models\Song;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class ReelWithSongTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    private function createSong(): Song
    {
        return Song::query()->create([
            'title' => 'Sunset Drive',
            'artist' => 'Neon Wave',
            'audio_disk' => 'local',
            'audio_path' => 'songs/sunset-drive.mp3',
            'duration_sec' => 180,
            'uploaded_by' => User::factory()->create()->id,
        ]);
    }

    public function test_a_reel_can_be_created_with_a_song_and_start_offset(): void
    {
        $song = $this->createSong();
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/v1/reels', [
            'media' => UploadedFile::fake()->create('clip.mp4', 500, 'video/mp4'),
            'song_id' => $song->id,
            'song_start_sec' => 45,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.song.id', $song->id);
        $response->assertJsonPath('data.song.title', 'Sunset Drive');
        $response->assertJsonPath('data.song_start_sec', 45);

        $reel = Post::query()->findOrFail($response->json('data.id'));
        $this->assertSame($song->id, $reel->song_id);
        $this->assertSame(45, $reel->song_start_sec);
    }

    public function test_a_reel_can_be_created_without_a_song(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/v1/reels', [
            'media' => UploadedFile::fake()->create('clip.mp4', 500, 'video/mp4'),
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.song', null);
        $response->assertJsonPath('data.song_start_sec', 0);
    }

    public function test_creating_a_reel_with_a_nonexistent_song_id_fails_validation(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/reels', [
            'media' => UploadedFile::fake()->create('clip.mp4', 500, 'video/mp4'),
            'song_id' => 999999,
        ])->assertUnprocessable();
    }
}
