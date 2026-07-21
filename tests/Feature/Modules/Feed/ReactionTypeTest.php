<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Feed;

use App\Core\Auth\Domain\Models\User;
use App\Modules\Feed\Domain\Models\ReactionType;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class ReactionTypeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    public function test_public_index_lists_reaction_types_without_a_gate(): void
    {
        ReactionType::query()->create(['key' => 'fire', 'label' => 'Fire', 'emoji' => '🔥', 'sort_order' => 0]);

        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/reaction-types')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.key', 'fire');
    }

    public function test_admin_can_create_a_reaction_type_with_an_uploaded_icon(): void
    {
        Storage::fake('public');
        Sanctum::actingAs(User::factory()->admin()->create());

        $response = $this->postJson('/api/v1/admin/reaction-types', [
            'key' => 'clap',
            'label' => 'Clap',
            'icon' => UploadedFile::fake()->image('clap.png'),
            'sort_order' => 0,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.key', 'clap');
        $response->assertJsonPath('data.icon_url', fn ($url) => is_string($url) && $url !== '');

        $reactionType = ReactionType::query()->where('key', 'clap')->firstOrFail();
        Storage::disk('public')->assertExists($reactionType->icon_path);
    }

    public function test_creating_a_reaction_type_rejects_a_duplicate_key(): void
    {
        ReactionType::query()->create(['key' => 'clap', 'label' => 'Clap', 'sort_order' => 0]);
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->postJson('/api/v1/admin/reaction-types', ['key' => 'clap', 'label' => 'Clap Again'])
            ->assertUnprocessable();
    }

    public function test_admin_can_update_a_reaction_types_label_and_the_key_stays_unchanged(): void
    {
        $reactionType = ReactionType::query()->create(['key' => 'clap', 'label' => 'Clap', 'sort_order' => 0]);
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->putJson("/api/v1/admin/reaction-types/{$reactionType->id}", ['label' => 'Applause'])
            ->assertOk()
            ->assertJsonPath('data.label', 'Applause')
            ->assertJsonPath('data.key', 'clap');
    }

    public function test_admin_can_delete_a_reaction_type_and_its_icon_file_is_removed(): void
    {
        Storage::fake('public');
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->postJson('/api/v1/admin/reaction-types', [
            'key' => 'clap',
            'label' => 'Clap',
            'icon' => UploadedFile::fake()->image('clap.png'),
        ])->assertCreated();

        $reactionType = ReactionType::query()->where('key', 'clap')->firstOrFail();
        $iconPath = $reactionType->icon_path;

        $this->deleteJson("/api/v1/admin/reaction-types/{$reactionType->id}")->assertOk();

        $this->assertDatabaseMissing('reaction_types', ['id' => $reactionType->id]);
        Storage::disk('public')->assertMissing($iconPath);
    }

    public function test_a_non_admin_gets_403_on_every_admin_reaction_type_endpoint(): void
    {
        $reactionType = ReactionType::query()->create(['key' => 'clap', 'label' => 'Clap', 'sort_order' => 0]);
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/admin/reaction-types')->assertForbidden();
        $this->postJson('/api/v1/admin/reaction-types', ['key' => 'x', 'label' => 'X'])->assertForbidden();
        $this->putJson("/api/v1/admin/reaction-types/{$reactionType->id}", ['label' => 'X'])->assertForbidden();
        $this->deleteJson("/api/v1/admin/reaction-types/{$reactionType->id}")->assertForbidden();
    }

    public function test_reacting_with_an_unknown_key_is_rejected_and_a_seeded_key_still_works(): void
    {
        ReactionType::query()->create(['key' => 'like', 'label' => 'Like', 'emoji' => '👍', 'sort_order' => 0]);

        $author = User::factory()->create();
        Sanctum::actingAs($author);
        $postId = $this->postJson('/api/v1/posts', ['body' => 'Hello'])->assertCreated()->json('data.id');

        $reactor = User::factory()->create();
        Sanctum::actingAs($reactor);

        $this->postJson("/api/v1/posts/{$postId}/like", ['type' => 'does_not_exist'])->assertUnprocessable();

        $this->postJson("/api/v1/posts/{$postId}/like", ['type' => 'like'])
            ->assertOk()
            ->assertJsonPath('data.my_reaction', 'like')
            ->assertJsonPath('data.likes_count', 1);
    }
}
