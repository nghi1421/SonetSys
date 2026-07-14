<?php

declare(strict_types=1);

namespace App\Modules\Feed\Http\Requests;

use App\Modules\Feed\Application\DTOs\CreatePostData;
use App\Modules\Feed\Domain\Enums\PostVisibility;
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
            'body' => ['required_without:shared_post_id', 'nullable', 'string', 'max:10000'],
            'visibility' => ['sometimes', new Enum(PostVisibility::class)],
            'shared_post_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('posts', 'id')->where(function ($query): void {
                    $query->where('tenant_id', $this->user()?->tenant_id);
                }),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->user()?->tenant_id === null) {
                $validator->errors()->add('tenant', 'Only tenant members can create posts.');
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
        );
    }
}
