<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Song;

use App\Core\Auth\Domain\Models\User;
use App\Modules\Song\Domain\Models\Song;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class SongAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    public function test_admin_can_create_a_song_with_an_uploaded_audio_file(): void
    {
        Storage::fake('public');
        Sanctum::actingAs(User::factory()->admin()->create());

        $response = $this->postJson('/api/v1/admin/songs', [
            'title' => 'Sunset Drive',
            'artist' => 'Neon Wave',
            'audio' => UploadedFile::fake()->create('sunset-drive.mp3', 2048, 'audio/mpeg'),
            'duration_sec' => 180,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.title', 'Sunset Drive');
        $response->assertJsonPath('data.artist', 'Neon Wave');
        $response->assertJsonPath('data.audio_url', fn ($url) => is_string($url) && $url !== '');

        $song = Song::query()->where('title', 'Sunset Drive')->firstOrFail();
        Storage::disk('public')->assertExists($song->audio_path);
    }

    public function test_creating_a_song_without_an_audio_file_is_rejected(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->postJson('/api/v1/admin/songs', ['title' => 'No Audio'])
            ->assertUnprocessable();
    }

    public function test_admin_can_update_a_songs_title_without_replacing_the_audio(): void
    {
        Storage::fake('public');
        Sanctum::actingAs(User::factory()->admin()->create());

        $created = $this->postJson('/api/v1/admin/songs', [
            'title' => 'Original Title',
            'audio' => UploadedFile::fake()->create('song.mp3', 1024, 'audio/mpeg'),
        ])->assertCreated();

        $songId = $created->json('data.id');
        $originalAudioUrl = $created->json('data.audio_url');

        $response = $this->putJson("/api/v1/admin/songs/{$songId}", ['title' => 'Updated Title']);

        $response->assertOk();
        $response->assertJsonPath('data.title', 'Updated Title');
        $response->assertJsonPath('data.audio_url', $originalAudioUrl);
    }

    public function test_admin_can_delete_a_song_and_its_audio_file_is_removed(): void
    {
        Storage::fake('public');
        Sanctum::actingAs(User::factory()->admin()->create());

        $created = $this->postJson('/api/v1/admin/songs', [
            'title' => 'To Delete',
            'audio' => UploadedFile::fake()->create('song.mp3', 1024, 'audio/mpeg'),
        ])->assertCreated();

        $song = Song::query()->where('title', 'To Delete')->firstOrFail();
        $audioPath = $song->audio_path;

        $this->deleteJson("/api/v1/admin/songs/{$song->id}")->assertOk();

        $this->assertDatabaseMissing('songs', ['id' => $song->id]);
        Storage::disk('public')->assertMissing($audioPath);
    }

    public function test_a_non_admin_gets_403_on_every_admin_song_endpoint(): void
    {
        Storage::fake('public');
        Sanctum::actingAs(User::factory()->admin()->create());

        $song = Song::query()->create([
            'title' => 'Existing',
            'audio_disk' => 'local',
            'audio_path' => 'songs/existing.mp3',
            'uploaded_by' => User::factory()->create()->id,
        ]);

        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/admin/songs')->assertForbidden();
        $this->postJson('/api/v1/admin/songs', [
            'title' => 'X',
            'audio' => UploadedFile::fake()->create('x.mp3', 512, 'audio/mpeg'),
        ])->assertForbidden();
        $this->putJson("/api/v1/admin/songs/{$song->id}", ['title' => 'X'])->assertForbidden();
        $this->deleteJson("/api/v1/admin/songs/{$song->id}")->assertForbidden();
    }
}
