<?php

declare(strict_types=1);

namespace Database\Seeders\Support;

use App\Core\Auth\Domain\Models\User;
use App\Modules\Feed\Domain\Models\Comment;
use App\Modules\Feed\Domain\Models\Post;
use App\Modules\Report\Application\Services\ReportService;
use App\Modules\Report\Domain\Enums\ReportReason;

final class DemoReportStep
{
    private const POST_REPORT_COUNT = 8;

    private const COMMENT_REPORT_COUNT = 4;

    private const DISMISS_EVERY_NTH = 3;

    public function __construct(
        private readonly ReportService $reports,
    ) {}

    public function run(): void
    {
        $reviewerId = User::query()->where('email', 'admin@sonetsys.test')->value('id');
        $userIds = User::query()->pluck('id')->all();

        $index = $this->reportTargets('post', Post::class, self::POST_REPORT_COUNT, $userIds, $reviewerId, 0);
        $this->reportTargets('comment', Comment::class, self::COMMENT_REPORT_COUNT, $userIds, $reviewerId, $index);
    }

    /**
     * @param  class-string<Post|Comment>  $modelClass
     * @param  list<int>  $userIds
     */
    private function reportTargets(
        string $morphAlias,
        string $modelClass,
        int $count,
        array $userIds,
        ?int $reviewerId,
        int $index,
    ): int {
        $targets = $modelClass::query()->inRandomOrder()->limit($count)->get();

        foreach ($targets as $target) {
            $reporterId = fake()->randomElement(array_diff($userIds, [(int) $target->author_id]));

            $report = $this->reports->submit(
                (int) $reporterId,
                $morphAlias,
                $target->id,
                fake()->randomElement(ReportReason::cases()),
                fake()->boolean(60) ? fake()->sentence() : null,
            );

            if ($reviewerId !== null && $index % self::DISMISS_EVERY_NTH === 0) {
                $this->reports->dismiss($report->id, $reviewerId);
            }

            $index++;
        }

        return $index;
    }
}
