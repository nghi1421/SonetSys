<?php

declare(strict_types=1);

namespace App\Core\Settings\Domain\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A singleton — always exactly one row, seeded by its migration.
 */
final class SystemSetting extends Model
{
    protected $fillable = [
        'cache_driver',
        'redis_client',
        'redis_host',
        'redis_port',
        'redis_password',
        'redis_database',
        'mail_host',
        'mail_port',
        'mail_username',
        'mail_password',
        'mail_encryption',
        'mail_from_address',
        'mail_from_name',
        'max_upload_size_kb',
    ];

    protected function casts(): array
    {
        return [
            'redis_port' => 'integer',
            'redis_database' => 'integer',
            'mail_port' => 'integer',
            'max_upload_size_kb' => 'integer',
        ];
    }
}
