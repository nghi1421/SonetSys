<?php

declare(strict_types=1);

namespace App\Core\Settings\Application\Services;

use App\Core\Settings\Domain\Models\SystemSetting;
use Illuminate\Support\Facades\Crypt;

/**
 * Overrides the in-memory config() values that Laravel's cache/Redis/mail
 * managers read lazily, from the site's single system_settings row —
 * mirroring StorageService's "resolve fresh at the point of use" pattern
 * rather than writing to .env.
 *
 * Known limitation: apply() is called once per app boot (see
 * SystemSettingsServiceProvider), which means an HTTP request always sees
 * the latest saved settings, but a long-running `queue:work` process only
 * re-reads them on worker restart — the same operational requirement
 * Laravel already has for .env changes (`php artisan queue:restart`).
 */
final class SystemSettingApplier
{
    private ?SystemSetting $settingCache = null;

    public function apply(): void
    {
        $setting = $this->setting();

        config([
            'cache.default' => $setting->cache_driver,
            'database.redis.default.host' => $setting->redis_host,
            'database.redis.default.port' => (string) $setting->redis_port,
            'database.redis.default.password' => $this->decrypt($setting->redis_password),
            'database.redis.default.database' => (string) $setting->redis_database,
            'database.redis.cache.host' => $setting->redis_host,
            'database.redis.cache.port' => (string) $setting->redis_port,
            'database.redis.cache.password' => $this->decrypt($setting->redis_password),
            'database.redis.client' => $setting->redis_client,
            'mail.mailers.smtp.host' => $setting->mail_host,
            'mail.mailers.smtp.port' => $setting->mail_port,
            'mail.mailers.smtp.username' => $setting->mail_username,
            'mail.mailers.smtp.password' => $this->decrypt($setting->mail_password),
            'mail.mailers.smtp.scheme' => $setting->mail_encryption,
            'mail.from.address' => $setting->mail_from_address,
            'mail.from.name' => $setting->mail_from_name,
        ]);
    }

    public function maxUploadKb(): int
    {
        return $this->setting()->max_upload_size_kb;
    }

    private function setting(): SystemSetting
    {
        return $this->settingCache ??= SystemSetting::query()->firstOrFail();
    }

    private function decrypt(?string $value): ?string
    {
        return $value !== null ? Crypt::decryptString($value) : null;
    }
}
