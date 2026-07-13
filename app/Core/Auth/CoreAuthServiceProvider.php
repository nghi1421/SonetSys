<?php

declare(strict_types=1);

namespace App\Core\Auth;

use App\Core\Auth\Application\Contracts\UserRepositoryInterface;
use App\Core\Auth\Domain\Models\User;
use App\Core\Auth\Infrastructure\Repositories\EloquentUserRepository;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class CoreAuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
    }

    public function boot(): void
    {
        // Every `permissions.slug` row doubles as a Gate ability — no per-permission
        // Gate::define() needed. Returning null (not false) lets an unmatched ability
        // fall through to Laravel's default deny instead of short-circuiting it.
        Gate::before(function (User $user, string $ability): ?bool {
            return $user->hasPermission($ability) ? true : null;
        });

        // morphMap() calls across providers merge — Feed registers 'post'/'comment'
        // separately, this is the stable alias for polymorphic relations to User.
        Relation::morphMap([
            'user' => User::class,
        ]);
    }
}
