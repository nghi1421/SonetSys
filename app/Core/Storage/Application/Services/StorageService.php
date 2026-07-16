<?php

declare(strict_types=1);

namespace App\Core\Storage\Application\Services;

use App\Core\Storage\Domain\Enums\StorageDriver;
use App\Core\Tenancy\Application\Contracts\TenantRepositoryInterface;
use App\Core\Tenancy\Domain\Models\Tenant;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

/**
 * Resolves the Flysystem disk a tenant should use for new uploads and for
 * reading/deleting existing files, based on the tenant's `storage_config`.
 *
 * Known limitation: deletion/read always use the tenant's CURRENT config for
 * a given driver, not a historical snapshot from upload time. If a tenant
 * replaces their S3 credentials/bucket entirely (not just tweaking a field),
 * files uploaded under the old credentials become unreachable through the
 * app — migrating existing files to new credentials is a separate concern
 * this system doesn't attempt to solve.
 */
final class StorageService
{
    private const LOCAL_DISK = 'public';

    /** @var array<int, ?Tenant> memoizes tenant lookups for this request/instance */
    private array $tenantCache = [];

    public function __construct(
        private readonly TenantRepositoryInterface $tenants,
    ) {}

    /**
     * @return array{disk: string, path: string}
     */
    public function store(UploadedFile $file, int $tenantId, string $directory): array
    {
        $driver = $this->driverFor($tenantId);
        $path = $this->filesystemFor($tenantId, $driver)->putFile($directory, $file);

        if ($path === false) {
            throw new \RuntimeException('Failed to store the uploaded file.');
        }

        return ['disk' => $driver, 'path' => $path];
    }

    public function delete(int $tenantId, string $disk, string $path): void
    {
        $this->filesystemFor($tenantId, $disk)->delete($path);
    }

    public function url(int $tenantId, string $disk, string $path): string
    {
        return $this->filesystemFor($tenantId, $disk)->url($path);
    }

    private function driverFor(int $tenantId): string
    {
        $config = $this->tenantFor($tenantId)?->storage_config;

        return $config['driver'] ?? StorageDriver::Local->value;
    }

    private function tenantFor(int $tenantId): ?Tenant
    {
        if (! array_key_exists($tenantId, $this->tenantCache)) {
            $this->tenantCache[$tenantId] = $this->tenants->findById($tenantId);
        }

        return $this->tenantCache[$tenantId];
    }

    private function filesystemFor(int $tenantId, string $disk): Filesystem
    {
        if ($disk !== StorageDriver::S3->value) {
            return Storage::disk(self::LOCAL_DISK);
        }

        return Storage::build($this->s3ConfigFor($tenantId));
    }

    /**
     * @return array<string, mixed>
     */
    private function s3ConfigFor(int $tenantId): array
    {
        $config = $this->tenantFor($tenantId)?->storage_config ?? [];

        return [
            'driver' => 's3',
            'key' => $config['key'] ?? null,
            'secret' => isset($config['secret']) ? Crypt::decryptString($config['secret']) : null,
            'region' => $config['region'] ?? null,
            'bucket' => $config['bucket'] ?? null,
            'endpoint' => $config['endpoint'] ?? null,
            'use_path_style_endpoint' => $config['use_path_style_endpoint'] ?? false,
            'throw' => true,
        ];
    }
}
