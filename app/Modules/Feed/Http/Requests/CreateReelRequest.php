<?php

declare(strict_types=1);

namespace App\Modules\Feed\Http\Requests;

use App\Core\Settings\Application\Services\SystemSettingApplier;
use App\Modules\Feed\Application\DTOs\CreatePostData;
use App\Modules\Feed\Domain\Enums\MediaType;
use App\Modules\Feed\Domain\Enums\PostVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateReelRequest extends FormRequest
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
            'media' => ['required', 'file', 'max:'.$this->settings->maxUploadKb(), 'mimes:mp4,mov,webm'],
            'body' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'mentioned_user_ids' => ['sometimes', 'array'],
            'mentioned_user_ids.*' => ['integer', Rule::exists('users', 'id')],
        ];
    }

    public function toDto(): CreatePostData
    {
        $user = $this->user();

        return new CreatePostData(
            body: (string) ($this->validated('body') ?? ''),
            visibility: PostVisibility::Members,
            authorId: (int) $user->id,
            media: $this->file('media'),
            mediaType: MediaType::Video,
            mentionedUserIds: array_map('intval', (array) ($this->validated('mentioned_user_ids') ?? [])),
            isReel: true,
        );
    }
}
