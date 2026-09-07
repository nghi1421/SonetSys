<?php

declare(strict_types=1);

namespace App\Modules\Song\Application\DTOs;

use Illuminate\Http\UploadedFile;

final readonly class UpdateSongData
{
    public function __construct(
        public string $title,
        public ?string $artist = null,
        public ?UploadedFile $audio = null,
        public ?UploadedFile $cover = null,
        public ?int $durationSec = null,
    ) {}
}
