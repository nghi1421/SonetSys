<?php

declare(strict_types=1);

namespace App\Modules\Feed\Http\Requests;

use App\Core\Settings\Application\Services\SystemSettingApplier;
use App\Modules\Feed\Application\Contracts\GroupAccessCheckerInterface;
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
    public function __construct(
        private readonly GroupAccessCheckerInterface $groupAccess,
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
            'body' => ['required_without_all:shared_post_id,media,sticker_key,location_name', 'nullable', 'string', 'max:10000'],
            'visibility' => ['sometimes', new Enum(PostVisibility::class)],
            'shared_post_id' => ['sometimes', 'nullable', 'integer', Rule::exists('posts', 'id')],
            'media' => ['sometimes', 'nullable', 'file', 'max:'.$this->settings->maxUploadKb(), 'mimes:jpg,jpeg,png,gif,webp,mp4,mov,webm'],
            'media_type' => ['required_with:media', 'nullable', Rule::in([MediaType::Image->value, MediaType::Video->value])],
            'sticker_key' => ['sometimes', 'nullable', new Enum(StickerKey::class)],
            'location_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'location_lat' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'location_lng' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'mentioned_user_ids' => ['sometimes', 'array'],
            'mentioned_user_ids.*' => ['integer', Rule::exists('users', 'id')],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->hasFile('media') && $this->filled('sticker_key')) {
                $validator->errors()->add('media', 'Choose either a photo/video or a sticker, not both.');
            }

            $locationFields = [$this->input('location_name'), $this->input('location_lat'), $this->input('location_lng')];
            $filledCount = count(array_filter($locationFields, fn ($value) => $value !== null && $value !== ''));

            if ($filledCount > 0 && $filledCount < 3) {
                $validator->errors()->add('location_name', 'Provide a location name, latitude, and longitude together, or omit all three.');
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

            if ($sharedPost === null) {
                return;
            }

            $isPrivateToOthers = $sharedPost->visibility === PostVisibility::Private
                && $sharedPost->author_id !== $user?->id;

            $isInaccessibleGroupPost = $sharedPost->group_id !== null
                && ! $this->groupAccess->canView((int) $sharedPost->group_id, (int) $user?->id);

            if ($isPrivateToOthers || $isInaccessibleGroupPost) {
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
                : PostVisibility::Members,
            authorId: (int) $user->id,
            sharedPostId: $this->filled('shared_post_id') ? (int) $this->validated('shared_post_id') : null,
            media: $this->file('media'),
            mediaType: $this->filled('media_type') ? MediaType::from((string) $this->validated('media_type')) : null,
            stickerKey: $this->filled('sticker_key') ? (string) $this->validated('sticker_key') : null,
            locationName: $this->filled('location_name') ? (string) $this->validated('location_name') : null,
            locationLat: $this->filled('location_lat') ? (float) $this->validated('location_lat') : null,
            locationLng: $this->filled('location_lng') ? (float) $this->validated('location_lng') : null,
            mentionedUserIds: array_map('intval', (array) ($this->validated('mentioned_user_ids') ?? [])),
        );
    }
}
