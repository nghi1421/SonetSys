<?php

declare(strict_types=1);

namespace App\Modules\Story\Http\Requests;

use App\Core\Settings\Application\Services\SystemSettingApplier;
use App\Core\Storage\Domain\Enums\MediaType;
use App\Modules\Story\Application\DTOs\CreateStoryData;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateStoryRequest extends FormRequest
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
            'media' => ['required', 'file', 'max:'.$this->settings->maxUploadKb(), 'mimes:jpg,jpeg,png,gif,webp,mp4,mov,webm'],
            'media_type' => ['required', Rule::in([MediaType::Image->value, MediaType::Video->value])],
            'caption' => ['sometimes', 'nullable', 'string', 'max:200'],
            'song_id' => ['sometimes', 'nullable', 'integer', Rule::exists('songs', 'id')],
            'song_start_sec' => ['sometimes', 'nullable', 'integer', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->hasFile('media') || ! $this->filled('media_type')) {
                return;
            }

            $isVideoFile = str_starts_with((string) $this->file('media')?->getMimeType(), 'video/');
            $declaredVideo = $this->input('media_type') === MediaType::Video->value;

            if ($isVideoFile !== $declaredVideo) {
                $validator->errors()->add('media_type', 'The media type does not match the uploaded file.');
            }
        });
    }

    public function toDto(): CreateStoryData
    {
        return new CreateStoryData(
            authorId: (int) $this->user()->id,
            media: $this->file('media'),
            mediaType: MediaType::from((string) $this->validated('media_type')),
            caption: $this->filled('caption') ? (string) $this->validated('caption') : null,
            songId: $this->filled('song_id') ? (int) $this->validated('song_id') : null,
            songStartSec: (int) ($this->validated('song_start_sec') ?? 0),
        );
    }
}
