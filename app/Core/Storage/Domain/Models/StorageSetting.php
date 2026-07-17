<?php

declare(strict_types=1);

namespace App\Core\Storage\Domain\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A singleton — always exactly one row, seeded by its migration.
 */
final class StorageSetting extends Model
{
    protected $fillable = [
        'driver',
        'bucket',
        'region',
        'key',
        'secret',
        'endpoint',
        'use_path_style_endpoint',
    ];

    protected function casts(): array
    {
        return [
            'use_path_style_endpoint' => 'boolean',
        ];
    }
}
