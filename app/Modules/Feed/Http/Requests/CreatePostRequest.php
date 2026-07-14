<?php

declare(strict_types=1);

namespace App\Modules\Feed\Http\Requests;

use App\Modules\Feed\Application\DTOs\CreatePostData;
use App\Modules\Feed\Domain\Enums\MediaType;
use App\Modules\Feed\Domain\Enums\PostVisibility;
use App\Modules\Feed\Domain\Enums\StickerKey;
use App\Modules\Feed\Domain\Models\Post;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

final class CreatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => ['required_without_all:shared_post_id,media,sticker_key', 'nullable', 'string', 'max:10000'],
            'visibility' => ['sometimes', new Enum(PostVisibility::class)],
            'shared_post_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('posts', 'id')->where(function ($query): void {
                    $query->where('tenant_id', $this->user()?->tenant_id);
                }),
            ],
            'media' => ['sometimes', 'nullable', 'file', 'max:20480', 'mimes:jpg,jpeg,png,gif,webp,mp4,mov,webm'],
            'media_type' => ['required_with:media', 'nullable', Rule::in([MediaType::Image->value, MediaType::Video->value])],
            'sticker_key' => ['sometimes', 'nullable', new Enum(StickerKey::class)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->user()?->tenant_id === null) {
                $validator->errors()->add('tenant', 'Only tenant members can create posts.');
            }

            if ($this->hasFile('media') && $this->filled('sticker_key')) {
                $validator->errors()->add('media', 'Choose either a photo/video or a sticker, not both.');
            }

            if ($this->hasFile('media') && $this->filled('media_type')) {
                $isVideoFile = str_starts_with((string) $this->file('media')?->getMimeType(), 'video/');
                $declaredVideo = $this->input('media_type') === MediaType::Video->value;

                if ($isVideoFile !== $declaredVideo) {
                    $validator->errors()->add('media_type', 'The media type does not match the uploaded file.');
                }
            }

            $sharedPostId = $this->input('shared_post_id');

            if ($sharedPostId === null) {
                return;
            }

            $sharedPost = Post::query()->find($sharedPostId);
            $user = $this->user();

            if (
                $sharedPost !== null
                && $sharedPost->visibility === PostVisibility::Private
                && $sharedPost->author_id !== $user?->id
            ) {
                $validator->errors()->add('shared_post_id', 'You cannot share this post.');
            }
        });
    }

    public function toDto(): CreatePostData
    {
        $user = $this->user();

        return new CreatePostData(
            body: (string) ($this->validated('body') ?? ''),
            visibility: $this->has('visibility')
                ? PostVisibility::from((string) $this->validated('visibility'))
                : PostVisibility::TenantOnly,
            tenantId: (int) $user->tenant_id,
            authorId: (int) $user->id,
            sharedPostId: $this->filled('shared_post_id') ? (int) $this->validated('shared_post_id') : null,
            media: $this->file('media'),
            mediaType: $this->filled('media_type') ? MediaType::from((string) $this->validated('media_type')) : null,
            stickerKey: $this->filled('sticker_key') ? (string) $this->validated('sticker_key') : null,
        );
    }
}
