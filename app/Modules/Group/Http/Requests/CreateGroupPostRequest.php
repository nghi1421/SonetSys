<?php

declare(strict_types=1);

namespace App\Modules\Group\Http\Requests;

use App\Modules\Feed\Application\DTOs\CreatePostData;
use App\Modules\Feed\Domain\Enums\MediaType;
use App\Modules\Feed\Domain\Enums\PostVisibility;
use App\Modules\Feed\Domain\Enums\StickerKey;
use App\Modules\Group\Domain\Models\Group;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

final class CreateGroupPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => ['required_without_all:media,sticker_key', 'nullable', 'string', 'max:10000'],
            'media' => ['sometimes', 'nullable', 'file', 'max:20480', 'mimes:jpg,jpeg,png,gif,webp,mp4,mov,webm'],
            'media_type' => ['required_with:media', 'nullable', Rule::in([MediaType::Image->value, MediaType::Video->value])],
            'sticker_key' => ['sometimes', 'nullable', new Enum(StickerKey::class)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->hasFile('media') && $this->filled('sticker_key')) {
                $validator->errors()->add('media', 'Choose either a photo/video or a sticker, not both.');
            }
        });
    }

    public function toDto(Group $group): CreatePostData
    {
        $user = $this->user();

        return new CreatePostData(
            body: (string) ($this->validated('body') ?? ''),
            visibility: PostVisibility::TenantOnly,
            tenantId: (int) $user->tenant_id,
            authorId: (int) $user->id,
            groupId: $group->id,
            media: $this->file('media'),
            mediaType: $this->filled('media_type') ? MediaType::from((string) $this->validated('media_type')) : null,
            stickerKey: $this->filled('sticker_key') ? (string) $this->validated('sticker_key') : null,
        );
    }
}
