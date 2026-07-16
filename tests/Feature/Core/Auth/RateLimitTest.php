<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_sixth_login_attempt_within_a_minute_is_throttled(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->postJson('/api/v1/auth/login', ['email' => 'nobody@example.com', 'password' => 'wrong'])
                ->assertStatus(422);
        }

        $this->postJson('/api/v1/auth/login', ['email' => 'nobody@example.com', 'password' => 'wrong'])
            ->assertStatus(429);
    }

    public function test_the_fourth_forgot_password_attempt_within_a_minute_is_throttled(): void
    {
        for ($i = 1; $i <= 3; $i++) {
            $this->postJson('/api/v1/auth/forgot-password', ['email' => 'nobody@example.com'])
                ->assertOk();
        }

        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'nobody@example.com'])
            ->assertStatus(429);
    }

    public function test_spamming_forgot_password_does_not_exhaust_the_logins_rate_limit_budget(): void
    {
        for ($i = 1; $i <= 3; $i++) {
            $this->postJson('/api/v1/auth/forgot-password', ['email' => 'nobody@example.com'])
                ->assertOk();
        }

        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'nobody@example.com'])
            ->assertStatus(429);

        $this->postJson('/api/v1/auth/login', ['email' => 'nobody@example.com', 'password' => 'wrong'])
            ->assertStatus(422);
    }
}
