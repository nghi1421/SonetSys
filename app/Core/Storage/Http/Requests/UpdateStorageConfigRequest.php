<?php

declare(strict_types=1);

namespace App\Core\Storage\Http\Requests;

use App\Core\Storage\Application\DTOs\StorageConfigData;
use App\Core\Storage\Domain\Enums\StorageDriver;
use App\Core\Storage\Http\Rules\SafeEndpointRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class UpdateStorageConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'driver' => ['required', new Enum(StorageDriver::class)],
            'bucket' => ['required_if:driver,s3', 'nullable', 'string', 'max:255'],
            'region' => ['required_if:driver,s3', 'nullable', 'string', 'max:64'],
            'key' => ['required_if:driver,s3', 'nullable', 'string', 'max:255'],
            'secret' => ['sometimes', 'nullable', 'string', 'max:255'],
            'endpoint' => ['sometimes', 'nullable', 'string', 'max:255', 'url', new SafeEndpointRule],
            'use_path_style_endpoint' => ['sometimes', 'boolean'],
        ];
    }

    public function toDto(): StorageConfigData
    {
        return new StorageConfigData(
            driver: StorageDriver::from((string) $this->validated('driver')),
            bucket: $this->validated('bucket'),
            region: $this->validated('region'),
            key: $this->validated('key'),
            secret: $this->filled('secret') ? (string) $this->validated('secret') : null,
            endpoint: $this->validated('endpoint'),
            usePathStyleEndpoint: (bool) $this->boolean('use_path_style_endpoint'),
        );
    }
}
