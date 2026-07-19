<?php

declare(strict_types=1);

namespace App\Modules\Report\Application\Contracts;

use App\Modules\Report\Domain\Models\Report;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ReportRepositoryInterface
{
    public function create(array $attributes): Report;

    /**
     * Query helper scoped to a still-pending report — returns null when the
     * report doesn't exist or has already been reviewed, letting the service
     * layer decide how to fail (a 422 ValidationException, not a raw 404).
     */
    public function findPendingOrFail(int $id): ?Report;

    public function pendingPaginated(int $page, int $perPage): LengthAwarePaginator;

    public function updateStatus(Report $report, array $attributes): Report;

    public function existsForReporterAndTarget(int $reporterId, string $type, int $id): bool;
}
