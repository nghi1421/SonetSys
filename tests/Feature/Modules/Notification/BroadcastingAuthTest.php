<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Notification;

use App\Core\Auth\Domain\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class BroadcastingAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // phpunit.xml forces BROADCAST_CONNECTION=null for tests, and the
        // null/log drivers don't implement real channel authorization at
        // all — every request would "succeed" regardless of the channel
        // callback in routes/channels.php. Switch to the real Pusher
        // broadcaster (dummy credentials — auth signing is pure local HMAC,
        // no network call) so this test exercises the actual code path.
        config([
            'broadcasting.default' => 'pusher',
            'broadcasting.connections.pusher.key' => 'test_key',
            'broadcasting.connections.pusher.secret' => 'test_secret',
            'broadcasting.connections.pusher.app_id' => '000000',
            'broadcasting.connections.pusher.options.cluster' => 'mt1',
        ]);

        // Broadcast::channel() registers against whichever connection is
        // *default at call time* — routes/channels.php already ran once
        // during boot against the 'null' connection (phpunit.xml's forced
        // default), so re-run the same registration now that 'pusher' is
        // the default, or this connection has zero channels registered and
        // every request falls through to a 403 regardless of the callback.
        Broadcast::channel('user.{userId}', fn (User $user, int $userId): bool => $user->id === $userId);
    }

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $user = User::factory()->create();

        $this->postJson('/broadcasting/auth', [
            'channel_name' => "private-user.{$user->id}",
            'socket_id' => '1.1',
        ])->assertStatus(401);
    }

    public function test_a_user_can_authenticate_their_own_private_channel(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/broadcasting/auth', [
            'channel_name' => "private-user.{$user->id}",
            'socket_id' => '1.1',
        ])->assertOk()->assertJsonStructure(['auth']);
    }

    public function test_a_user_cannot_authenticate_another_users_private_channel(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/broadcasting/auth', [
            'channel_name' => "private-user.{$other->id}",
            'socket_id' => '1.1',
        ])->assertStatus(403);
    }
}
