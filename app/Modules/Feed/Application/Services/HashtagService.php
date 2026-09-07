<?php

declare(strict_types=1);

namespace App\Modules\Feed\Application\Services;

use App\Modules\Feed\Domain\Models\Comment;
use App\Modules\Feed\Domain\Models\Hashtag;
use App\Modules\Feed\Domain\Models\Post;
use Illuminate\Support\Str;

/**
 * Extracts #hashtags from a post/comment body and keeps the hashtaggables
 * pivot in sync with the current text — replace-all semantics via sync(),
 * so re-running this on an edit detaches any hashtag no longer present.
 */
final class HashtagService
{
    /**
     * Unicode-aware but intentionally simple: letters/numbers/underscore,
     * 1-50 chars. Not meant to catch every real-world edge case.
     */
    private const PATTERN = '/#([\p{L}\p{N}_]{1,50})/u';

    /**
     * Fenced code blocks and inline code spans, stripped before hashtag
     * matching — posts now render as markdown, so a "#" inside a code
     * sample (e.g. a C `#include`) must not become a hashtag.
     */
    private const CODE_PATTERN = '/```.*?```|`[^`\n]*`/us';

    public function extractAndAttach(string $body, Post|Comment $subject): void
    {
        $hashtagIds = collect($this->extractTags($body))
            ->map(fn (string $tag) => Hashtag::query()->firstOrCreate(['tag' => $tag])->id)
            ->all();

        $subject->hashtags()->sync($hashtagIds);
    }

    /**
     * @return list<string>
     */
    private function extractTags(string $body): array
    {
        $withoutCode = preg_replace(self::CODE_PATTERN, '', $body) ?? $body;

        preg_match_all(self::PATTERN, $withoutCode, $matches);

        return collect($matches[1] ?? [])
            ->map(fn (string $tag) => Str::lower($tag))
            ->unique()
            ->values()
            ->all();
    }
}
