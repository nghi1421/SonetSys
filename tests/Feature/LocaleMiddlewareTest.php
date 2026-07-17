<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\App;
use Tests\TestCase;

final class LocaleMiddlewareTest extends TestCase
{
    public function test_a_supported_locale_header_is_applied(): void
    {
        $this->withHeaders(['X-Locale' => 'en'])
            ->postJson('/api/v1/auth/register', [])
            ->assertStatus(422);

        $this->assertSame('en', App::getLocale());
    }

    public function test_an_unsupported_locale_header_falls_back_to_the_default_without_erroring(): void
    {
        $this->withHeaders(['X-Locale' => 'xx-not-a-real-locale'])
            ->postJson('/api/v1/auth/register', [])
            ->assertStatus(422);

        $this->assertSame(config('app.locale'), App::getLocale());
    }

    public function test_a_missing_locale_header_falls_back_to_the_default(): void
    {
        $this->postJson('/api/v1/auth/register', [])
            ->assertStatus(422);

        $this->assertSame(config('app.locale'), App::getLocale());
    }
}
