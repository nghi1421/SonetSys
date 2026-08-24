<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Story;

use App\Core\Auth\Domain\Models\User;
use App\Modules\Song\Domain\Models\Song;
use App\Modules\Story\Domain\Models\Story;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class StoryWithSongTest extends TestCase
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
            'title' => 'Midnight Run',
            'artist' => 'Chrome Pulse',
            'audio_disk' => 'local',
            'audio_path' => 'songs/midnight-run.mp3',
            'duration_sec' => 200,
            'uploaded_by' => User::factory()->create()->id,
        ]);
    }

    public function test_a_story_can_be_created_with_a_song_and_start_offset(): void
    {
        $song = $this->createSong();
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/v1/stories', [
            'media' => UploadedFile::fake()->image('photo.jpg'),
            'media_type' => 'image',
            'song_id' => $song->id,
            'song_start_sec' => 30,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.song.id', $song->id);
        $response->assertJsonPath('data.song.title', 'Midnight Run');
        $response->assertJsonPath('data.song_start_sec', 30);

        $story = Story::query()->findOrFail($response->json('data.id'));
        $this->assertSame($song->id, $story->song_id);
        $this->assertSame(30, $story->song_start_sec);
    }

    public function test_a_story_can_be_created_without_a_song(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/v1/stories', [
            'media' => UploadedFile::fake()->image('photo.jpg'),
            'media_type' => 'image',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.song', null);
        $response->assertJsonPath('data.song_start_sec', 0);
    }

    public function test_creating_a_story_with_a_nonexistent_song_id_fails_validation(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/stories', [
            'media' => UploadedFile::fake()->image('photo.jpg'),
            'media_type' => 'image',
            'song_id' => 999999,
        ])->assertUnprocessable();
    }
}
