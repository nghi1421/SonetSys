<?php

declare(strict_types=1);

namespace App\Core\Auth\Domain\Models;

use App\Core\Auth\Domain\Enums\RoleSlug;
use App\Core\Auth\Domain\Enums\UserStatus;
use App\Core\Auth\Domain\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

final class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
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

    /**
     * Overrides Laravel's default (which points at a web route this SPA
     * doesn't have) so the reset link lands on the frontend instead. Left
     * untyped to stay LSP-compatible with the untyped parent trait method
     * (Illuminate\Auth\Passwords\CanResetPassword).
     *
     * @param  string  $token
     */
    public function sendPasswordResetNotification(#[\SensitiveParameter] $token)
    {
        $url = rtrim((string) config('app.frontend_url'), '/').
            '/reset-password?token='.$token.'&email='.urlencode($this->email);

        $this->notify(new ResetPasswordNotification($url));
    }
}
