<?php

declare(strict_types=1);

namespace App\Core\Settings;

use App\Core\Settings\Application\Services\SystemSettingApplier;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

final class CoreSettingsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Guard against console commands that run before the first migrate
        // (e.g. `artisan migrate` itself, `artisan key:generate` in a fresh
        // install) where system_settings doesn't exist yet.
        if (! Schema::hasTable('system_settings')) {
            return;
        }

        $this->app->make(SystemSettingApplier::class)->apply();
    }
}
