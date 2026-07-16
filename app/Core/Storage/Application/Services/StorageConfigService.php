<?php

declare(strict_types=1);

namespace App\Core\Storage\Application\Services;

use App\Core\Storage\Application\DTOs\StorageConfigData;
use App\Core\Storage\Domain\Enums\StorageDriver;
use App\Core\Tenancy\Application\Contracts\TenantRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Crypt;

/**
 * Reads/writes a tenant's storage_config. The S3 secret is encrypted at
 * rest and never returned to the caller in plaintext or ciphertext — only
 * a boolean flag indicating one is configured (see currentFor()).
 */
final class StorageConfigService
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenants,
    ) {}

    /**
     * @return array{driver: string, bucket: ?string, region: ?string, key: ?string, endpoint: ?string, use_path_style_endpoint: bool, has_secret: bool}
     */
    public function currentFor(int $tenantId): array
    {
        $tenant = $this->tenants->findById($tenantId) ?? throw new ModelNotFoundException;
        $config = $tenant->storage_config ?? ['driver' => StorageDriver::Local->value];

        return [
            'driver' => $config['driver'] ?? StorageDriver::Local->value,
            'bucket' => $config['bucket'] ?? null,
            'region' => $config['region'] ?? null,
            'key' => $config['key'] ?? null,
            'endpoint' => $config['endpoint'] ?? null,
            'use_path_style_endpoint' => $config['use_path_style_endpoint'] ?? false,
            'has_secret' => isset($config['secret']),
        ];
    }

    public function update(int $tenantId, StorageConfigData $data): void
    {
        $tenant = $this->tenants->findById($tenantId) ?? throw new ModelNotFoundException;
        $existing = $tenant->storage_config ?? [];

        $config = ['driver' => $data->driver->value];

        if ($data->driver === StorageDriver::S3) {
            $config += [
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
                    : ($existing['secret'] ?? null),
            ];
        }

        $this->tenants->update($tenant, ['storage_config' => $config]);
    }
}
