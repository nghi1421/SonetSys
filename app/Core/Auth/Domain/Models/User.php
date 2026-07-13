<?php

declare(strict_types=1);

namespace App\Core\Auth\Domain\Models;

use App\Core\Auth\Domain\Enums\RoleSlug;
use App\Core\Auth\Domain\Enums\UserStatus;
use App\Core\Tenancy\Domain\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

final class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'tenant_id',
        'role_id',
        'name',
        'email',
        'password',
        'status',
        'avatar_url',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
            'last_login_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function hasPermission(string $slug): bool
    {
        return $this->role?->permissions->contains('slug', $slug) ?? false;
    }

    public function hasRole(RoleSlug|string $slug): bool
    {
        $slug = $slug instanceof RoleSlug ? $slug->value : $slug;

        return $this->role?->slug === $slug;
    }
}
