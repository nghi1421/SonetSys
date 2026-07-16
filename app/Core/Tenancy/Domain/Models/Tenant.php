<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Domain\Models;

use App\Core\Auth\Domain\Models\Role;
use App\Core\Auth\Domain\Models\User;
use App\Core\Tenancy\Domain\Enums\TenantStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'status',
        'enabled_modules',
        'settings',
        'storage_config',
    ];

    protected function casts(): array
    {
        return [
            'status' => TenantStatus::class,
            'enabled_modules' => 'array',
            'settings' => 'array',
            'storage_config' => 'array',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }
}
