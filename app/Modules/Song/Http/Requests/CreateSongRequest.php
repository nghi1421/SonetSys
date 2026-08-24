<?php

declare(strict_types=1);

namespace App\Modules\Song\Http\Requests;

use App\Core\Settings\Application\Services\SystemSettingApplier;
use App\Modules\Song\Application\DTOs\CreateSongData;
use Illuminate\Foundation\Http\FormRequest;

final class CreateSongRequest extends FormRequest
{
    public function __construct(
        private readonly SystemSettingApplier $settings,
    ) {
        parent::__construct();
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:191'],
            'artist' => ['nullable', 'string', 'max:191'],
            'audio' => ['required', 'file', 'mimes:mp3,wav,ogg,m4a,aac', 'max:'.$this->settings->maxUploadKb()],
            'cover' => ['nullable', 'file', 'image', 'max:'.$this->settings->maxUploadKb()],
            'duration_sec' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function toDto(): CreateSongData
    {
        return new CreateSongData(
            title: (string) $this->validated('title'),
            audio: $this->file('audio'),
            artist: $this->filled('artist') ? (string) $this->validated('artist') : null,
            cover: $this->file('cover'),
            durationSec: $this->filled('duration_sec') ? (int) $this->validated('duration_sec') : null,
        );
    }
}
