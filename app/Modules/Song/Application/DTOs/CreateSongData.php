<?php

declare(strict_types=1);

namespace App\Modules\Song\Application\DTOs;

use Illuminate\Http\UploadedFile;

final readonly class CreateSongData
{
    public function __construct(
        public string $title,
        public UploadedFile $audio,
        public ?string $artist = null,
        public ?UploadedFile $cover = null,
        public ?int $durationSec = null,
    ) {}
}
