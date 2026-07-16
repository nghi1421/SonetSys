<?php

declare(strict_types=1);

namespace App\Modules\Feed\Application\DTOs;

use App\Modules\Feed\Domain\Enums\MediaType;
use App\Modules\Feed\Domain\Enums\PostVisibility;
use Illuminate\Http\UploadedFile;

final readonly class CreatePostData
{
    public function __construct(
        public string $body,
        public PostVisibility $visibility,
        public int $authorId,
        public array $metadata = [],
        public ?int $sharedPostId = null,
        public ?int $groupId = null,
        public ?UploadedFile $media = null,
        public ?MediaType $mediaType = null,
        public ?string $stickerKey = null,
    ) {}
}
