<?php

declare(strict_types=1);

namespace App\Modules\Story\Application\DTOs;

use App\Core\Storage\Domain\Enums\MediaType;
use Illuminate\Http\UploadedFile;

final readonly class CreateStoryData
{
    public function __construct(
        public int $authorId,
        public UploadedFile $media,
        public MediaType $mediaType,
        public ?string $caption = null,
        public ?int $songId = null,
        public int $songStartSec = 0,
    ) {}
}
