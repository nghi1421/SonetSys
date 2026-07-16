<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Auth;

use App\Core\Auth\Domain\Models\User;
use App\Core\Auth\Domain\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

final class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_requesting_a_reset_link_for_a_registered_email_sends_a_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'known@example.com']);

        $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])
            ->assertOk();

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_requesting_a_reset_link_for_an_unknown_email_returns_the_same_generic_response(): void
    {
        Notification::fake();

        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'nobody@example.com'])
            ->assertOk()
            ->assertJsonPath('meta.message', fn ($message) => is_string($message));

        Notification::assertNothingSent();
    }

    public function test_resetting_with_a_valid_token_updates_the_password_and_revokes_prior_tokens(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'reset-me@example.com']);
        $oldToken = $user->createToken('api')->plainTextToken;

        $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])
            ->assertOk();

        $token = $this->extractToken($user);

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'brand-new-password',
        ])->assertOk();

        $this->assertTrue(Hash::check('brand-new-password', $user->fresh()->password));

        $this->withHeader('Authorization', 'Bearer '.$oldToken)
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized();
    }

    public function test_resetting_with_an_invalid_token_is_rejected(): void
    {
        $user = User::factory()->create(['email' => 'invalid-token@example.com']);

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'not-a-real-token',
            'email' => $user->email,
            'password' => 'brand-new-password',
        ])->assertStatus(422);
    }

    public function test_resetting_with_an_expired_token_is_rejected(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'expired-token@example.com']);

        $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])
            ->assertOk();

        $token = $this->extractToken($user);

        DB::table('user_password_reset_tokens')
            ->where('user_id', $user->id)
            ->update(['created_at' => now()->subHours(2)]);

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'brand-new-password',
        ])->assertStatus(422);
    }

    private function extractToken(User $user): string
    {
        $url = null;

        Notification::assertSentTo(
            $user,
            ResetPasswordNotification::class,
            function (ResetPasswordNotification $notification) use (&$url): bool {
                $url = $notification->url;

                return true;
            },
        );

        parse_str((string) parse_url((string) $url, PHP_URL_QUERY), $query);

        return (string) $query['token'];
    }
}
