<?php

declare(strict_types=1);

namespace App\Modules\Report\Infrastructure\Repositories;

use App\Modules\Report\Application\Contracts\ReportRepositoryInterface;
use App\Modules\Report\Domain\Enums\ReportStatus;
use App\Modules\Report\Domain\Models\Report;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentReportRepository implements ReportRepositoryInterface
{
    public function create(array $attributes): Report
    {
        return Report::query()->create($attributes);
    }

    public function findPendingOrFail(int $id): ?Report
    {
        return Report::query()
            ->where('id', $id)
            ->where('status', ReportStatus::Pending)
            ->first();
    }

    public function pendingPaginated(int $page, int $perPage): LengthAwarePaginator
    {
        return Report::query()
            ->where('status', ReportStatus::Pending)
            ->with(['reporter', 'reportable'])
            ->orderBy('id')
            ->paginate($perPage, page: $page);
    }

    public function updateStatus(Report $report, array $attributes): Report
    {
        $report->update($attributes);

        return $report->refresh();
    }

    public function existsForReporterAndTarget(int $reporterId, string $type, int $id): bool
    {
        return Report::query()
            ->where('reporter_id', $reporterId)
            ->where('reportable_type', $type)
            ->where('reportable_id', $id)
            ->exists();
    }
}
