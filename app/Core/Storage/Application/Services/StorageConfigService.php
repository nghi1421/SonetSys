<?php

declare(strict_types=1);

namespace App\Core\Storage\Application\Services;

use App\Core\Storage\Application\DTOs\StorageConfigData;
use App\Core\Storage\Domain\Enums\StorageDriver;
use App\Core\Storage\Domain\Models\StorageSetting;
use Illuminate\Support\Facades\Crypt;

/**
 * Reads/writes the site's single storage_settings row. The S3 secret is
 * encrypted at rest and never returned to the caller in plaintext or
 * ciphertext — only a boolean flag indicating one is configured (see
 * current()).
 */
final class StorageConfigService
{
    /**
     * @return array{driver: string, bucket: ?string, region: ?string, key: ?string, endpoint: ?string, use_path_style_endpoint: bool, has_secret: bool}
     */
    public function current(): array
    {
        $setting = StorageSetting::query()->firstOrFail();

        return [
            'driver' => $setting->driver,
            'bucket' => $setting->bucket,
            'region' => $setting->region,
            'key' => $setting->key,
            'endpoint' => $setting->endpoint,
            'use_path_style_endpoint' => $setting->use_path_style_endpoint,
            'has_secret' => $setting->secret !== null,
        ];
    }

    public function update(StorageConfigData $data): void
    {
        $setting = StorageSetting::query()->firstOrFail();

        $attributes = ['driver' => $data->driver->value];

        if ($data->driver === StorageDriver::S3) {
            $attributes += [
                'bucket' => $data->bucket,
                'region' => $data->region,
                'key' => $data->key,
                'endpoint' => $data->endpoint,
                'use_path_style_endpoint' => $data->usePathStyleEndpoint,
                // Leaving the secret field blank keeps the previously saved
                // one, so an admin can edit the bucket/region without having
                // to re-enter credentials every time.
                'secret' => $data->secret !== null
                    ? Crypt::encryptString($data->secret)
                    : $setting->secret,
            ];
        }

        $setting->update($attributes);
    }
}
