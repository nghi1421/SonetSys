<?php

declare(strict_types=1);

namespace App\Core\Settings\Application\Services;

use App\Core\Settings\Application\DTOs\SystemSettingData;
use App\Core\Settings\Domain\Models\SystemSetting;
use Illuminate\Support\Facades\Crypt;

/**
 * Reads/writes the site's single system_settings row. Mail and Redis
 * passwords are encrypted at rest and never returned to the caller in
 * plaintext or ciphertext — only a boolean flag indicating one is
 * configured (see current()), same convention as StorageConfigService.
 */
final class SystemSettingService
{
    /**
     * @return array{cache_driver: string, redis_client: string, redis_host: string, redis_port: int, redis_database: int, has_redis_password: bool, mail_host: string, mail_port: int, mail_username: ?string, mail_encryption: ?string, mail_from_address: string, mail_from_name: string, has_mail_password: bool, max_upload_size_kb: int}
     */
    public function current(): array
    {
        $setting = SystemSetting::query()->firstOrFail();

        return [
            'cache_driver' => $setting->cache_driver,
            'redis_client' => $setting->redis_client,
            'redis_host' => $setting->redis_host,
            'redis_port' => $setting->redis_port,
            'redis_database' => $setting->redis_database,
            'has_redis_password' => $setting->redis_password !== null,
            'mail_host' => $setting->mail_host,
            'mail_port' => $setting->mail_port,
            'mail_username' => $setting->mail_username,
            'mail_encryption' => $setting->mail_encryption,
            'mail_from_address' => $setting->mail_from_address,
            'mail_from_name' => $setting->mail_from_name,
            'has_mail_password' => $setting->mail_password !== null,
            'max_upload_size_kb' => $setting->max_upload_size_kb,
        ];
    }

    public function update(SystemSettingData $data): void
    {
        $setting = SystemSetting::query()->firstOrFail();

        $setting->update([
            'cache_driver' => $data->cacheDriver->value,
            'redis_client' => $data->redisClient->value,
            'redis_host' => $data->redisHost,
            'redis_port' => $data->redisPort,
            // Leaving a password field blank keeps the previously saved one,
            // so an admin can edit other fields without re-entering secrets.
            'redis_password' => $data->redisPassword !== null
                ? Crypt::encryptString($data->redisPassword)
                : $setting->redis_password,
            'redis_database' => $data->redisDatabase,
            'mail_host' => $data->mailHost,
            'mail_port' => $data->mailPort,
            'mail_username' => $data->mailUsername,
            'mail_password' => $data->mailPassword !== null
                ? Crypt::encryptString($data->mailPassword)
                : $setting->mail_password,
            'mail_encryption' => $data->mailEncryption,
            'mail_from_address' => $data->mailFromAddress,
            'mail_from_name' => $data->mailFromName,
            'max_upload_size_kb' => $data->maxUploadSizeKb,
        ]);
    }
}
