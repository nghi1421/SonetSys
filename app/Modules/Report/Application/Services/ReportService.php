<?php

declare(strict_types=1);

namespace App\Modules\Report\Application\Services;

use App\Modules\Feed\Application\Services\CommentService;
use App\Modules\Feed\Application\Services\PostService;
use App\Modules\Feed\Domain\Models\Comment;
use App\Modules\Feed\Domain\Models\Post;
use App\Modules\Report\Application\Contracts\ReportRepositoryInterface;
use App\Modules\Report\Domain\Enums\ReportReason;
use App\Modules\Report\Domain\Enums\ReportStatus;
use App\Modules\Report\Domain\Models\Report;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Depends one-directionally on Feed's PostService/CommentService for the
 * resolve-action delete — the same Group -> Feed precedent already
 * established elsewhere in this codebase.
 */
final class ReportService
{
    private const PER_PAGE = 25;

    public function __construct(
        private readonly ReportRepositoryInterface $reports,
        private readonly PostService $posts,
        private readonly CommentService $comments,
    ) {}

    public function submit(int $reporterId, string $type, int $id, ReportReason $reason, ?string $details): Report
    {
        $modelClass = Relation::getMorphedModel($type)
            ?? throw new InvalidArgumentException("Unknown reportable type [{$type}].");

        $content = $modelClass::query()->find($id);

        if ($content === null) {
            throw ValidationException::withMessages([
                'content' => 'This content no longer exists.',
            ]);
        }

        if ((int) $content->author_id === $reporterId) {
            throw ValidationException::withMessages([
                'content' => 'You cannot report your own content.',
            ]);
        }

        if ($this->reports->existsForReporterAndTarget($reporterId, $type, $id)) {
            throw ValidationException::withMessages([
                'content' => 'You have already reported this content.',
            ]);
        }

        return $this->reports->create([
            'reportable_type' => $type,
            'reportable_id' => $id,
            'reporter_id' => $reporterId,
            'reason' => $reason,
            'details' => $details,
            'status' => ReportStatus::Pending,
        ]);
    }

    public function resolve(int $reportId, int $reviewerId): Report
    {
        $report = $this->findPendingOrFail($reportId);

        $content = $report->reportable;

        if ($content instanceof Post) {
            $this->posts->delete($content);
        } elseif ($content instanceof Comment) {
            $this->comments->delete($content);
        }

        return $this->reports->updateStatus($report, [
            'status' => ReportStatus::Resolved,
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
        ]);
    }

    public function dismiss(int $reportId, int $reviewerId): Report
    {
        $report = $this->findPendingOrFail($reportId);

        return $this->reports->updateStatus($report, [
            'status' => ReportStatus::Dismissed,
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
        ]);
    }

    public function pendingQueue(int $page): LengthAwarePaginator
    {
        return $this->reports->pendingPaginated($page, self::PER_PAGE);
    }

    private function findPendingOrFail(int $reportId): Report
    {
        return $this->reports->findPendingOrFail($reportId)
            ?? throw ValidationException::withMessages([
                'report' => 'This report is not pending review.',
            ]);
    }
}
