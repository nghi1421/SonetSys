<?php

declare(strict_types=1);

namespace App\Modules\Group;

use App\Modules\Feed\Application\Contracts\GroupAccessCheckerInterface;
use App\Modules\Group\Application\Contracts\GroupMemberRepositoryInterface;
use App\Modules\Group\Application\Contracts\GroupRepositoryInterface;
use App\Modules\Group\Infrastructure\Repositories\EloquentGroupMemberRepository;
use App\Modules\Group\Infrastructure\Repositories\EloquentGroupRepository;
use App\Modules\Group\Infrastructure\Repositories\GroupAccessChecker;
use Illuminate\Support\ServiceProvider;

final class GroupServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(GroupRepositoryInterface::class, EloquentGroupRepository::class);
        $this->app->bind(GroupMemberRepositoryInterface::class, EloquentGroupMemberRepository::class);
        $this->app->bind(GroupAccessCheckerInterface::class, GroupAccessChecker::class);
    }
}
