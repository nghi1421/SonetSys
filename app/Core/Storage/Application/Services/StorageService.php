<?php

declare(strict_types=1);

namespace App\Core\Storage\Application\Services;

use App\Core\Storage\Domain\Enums\StorageDriver;
use App\Core\Storage\Domain\Models\StorageSetting;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

/**
 * Resolves the Flysystem disk to use for new uploads and for reading/
 * deleting existing files, based on the site's single storage_settings row.
 *
 * Known limitation: deletion/read always use the CURRENT config for a given
 * driver, not a historical snapshot from upload time. If the S3 credentials/
 * bucket are replaced entirely (not just tweaking a field), files uploaded
 * under the old credentials become unreachable through the app — migrating
 * existing files to new credentials is a separate concern this system
 * doesn't attempt to solve.
 */
final class StorageService
{
    private const LOCAL_DISK = 'public';

    private ?StorageSetting $settingCache = null;

    /**
     * @return array{disk: string, path: string}
     */
    public function store(UploadedFile $file, string $directory): array
    {
        $driver = $this->driver();
        $path = $this->filesystemFor($driver)->putFile($directory, $file);

        if ($path === false) {
            throw new \RuntimeException('Failed to store the uploaded file.');
        }

        return ['disk' => $driver, 'path' => $path];
    }

    public function delete(string $disk, string $path): void
    {
        $this->filesystemFor($disk)->delete($path);
    }

    public function url(string $disk, string $path): string
    {
        return $this->filesystemFor($disk)->url($path);
    }

    private function driver(): string
    {
        return $this->setting()->driver;
    }

    private function setting(): StorageSetting
    {
        return $this->settingCache ??= StorageSetting::query()->firstOrFail();
    }

    private function filesystemFor(string $disk): Filesystem
    {
        if ($disk !== StorageDriver::S3->value) {
            return Storage::disk(self::LOCAL_DISK);
        }

        return Storage::build($this->s3Config());
    }

    /**
     * @return array<string, mixed>
     */
    private function s3Config(): array
    {
        $setting = $this->setting();

        return [
            'driver' => 's3',
            'key' => $setting->key,
            'secret' => $setting->secret !== null ? Crypt::decryptString($setting->secret) : null,
            'region' => $setting->region,
            'bucket' => $setting->bucket,
            'endpoint' => $setting->endpoint,
            'use_path_style_endpoint' => $setting->use_path_style_endpoint,
            'throw' => true,
        ];
    }
}
