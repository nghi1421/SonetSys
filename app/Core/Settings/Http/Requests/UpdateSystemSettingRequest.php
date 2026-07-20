<?php

declare(strict_types=1);

namespace App\Core\Settings\Http\Requests;

use App\Core\Settings\Application\DTOs\SystemSettingData;
use App\Core\Settings\Domain\Enums\CacheDriver;
use App\Core\Settings\Domain\Enums\RedisClient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class UpdateSystemSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cache_driver' => ['required', new Enum(CacheDriver::class)],
            'redis_client' => ['required', new Enum(RedisClient::class)],
            'redis_host' => ['required', 'string', 'max:255'],
            'redis_port' => ['required', 'integer', 'between:1,65535'],
            'redis_password' => ['sometimes', 'nullable', 'string', 'max:255'],
            'redis_database' => ['required', 'integer', 'min:0'],
            'mail_host' => ['required', 'string', 'max:255'],
            'mail_port' => ['required', 'integer', 'between:1,65535'],
            'mail_username' => ['sometimes', 'nullable', 'string', 'max:255'],
            'mail_password' => ['sometimes', 'nullable', 'string', 'max:255'],
            'mail_encryption' => ['sometimes', 'nullable', 'string', 'max:20'],
            'mail_from_address' => ['required', 'email', 'max:255'],
            'mail_from_name' => ['required', 'string', 'max:255'],
            'max_upload_size_kb' => ['required', 'integer', 'min:1', 'max:512000'],
        ];
    }

    public function toDto(): SystemSettingData
    {
        return new SystemSettingData(
            cacheDriver: CacheDriver::from((string) $this->validated('cache_driver')),
            redisClient: RedisClient::from((string) $this->validated('redis_client')),
            redisHost: (string) $this->validated('redis_host'),
            redisPort: (int) $this->validated('redis_port'),
            redisPassword: $this->filled('redis_password') ? (string) $this->validated('redis_password') : null,
            redisDatabase: (int) $this->validated('redis_database'),
            mailHost: (string) $this->validated('mail_host'),
            mailPort: (int) $this->validated('mail_port'),
            mailUsername: $this->filled('mail_username') ? (string) $this->validated('mail_username') : null,
            mailPassword: $this->filled('mail_password') ? (string) $this->validated('mail_password') : null,
            mailEncryption: $this->filled('mail_encryption') ? (string) $this->validated('mail_encryption') : null,
            mailFromAddress: (string) $this->validated('mail_from_address'),
            mailFromName: (string) $this->validated('mail_from_name'),
            maxUploadSizeKb: (int) $this->validated('max_upload_size_kb'),
        );
    }
}
