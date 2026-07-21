<?php

declare(strict_types=1);

namespace App\Modules\Report\Http\Resources;

use App\Modules\Feed\Domain\Models\Comment;
use App\Modules\Feed\Domain\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AdminReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reporter' => [
                'id' => $this->whenLoaded('reporter', fn () => $this->reporter->id),
                'name' => $this->whenLoaded('reporter', fn () => $this->reporter->name),
            ],
            'reportable_type' => $this->reportable_type,
            'reportable_id' => $this->reportable_id,
            'excerpt' => $this->whenLoaded('reportable', fn () => $this->excerpt()),
            'post_id' => $this->whenLoaded('reportable', fn () => $this->linkedPostId()),
            'reason' => $this->reason->value,
            'details' => $this->details,
            'status' => $this->status->value,
            'reviewed_by' => $this->reviewed_by,
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }

    private function excerpt(): ?string
    {
        $content = $this->reportable;

        if ($content === null) {
            return null;
        }

        return str($content->body)->limit(140)->toString();
    }

    private function linkedPostId(): ?int
    {
        $content = $this->reportable;

        return match (true) {
            $content instanceof Post => $content->id,
            $content instanceof Comment => $content->post_id,
            default => null,
        };
    }
}
