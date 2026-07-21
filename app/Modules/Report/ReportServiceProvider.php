<?php

declare(strict_types=1);

namespace App\Modules\Report;

use App\Modules\Report\Application\Contracts\ReportRepositoryInterface;
use App\Modules\Report\Infrastructure\Repositories\EloquentReportRepository;
use Illuminate\Support\ServiceProvider;

final class ReportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ReportRepositoryInterface::class, EloquentReportRepository::class);
    }
}
