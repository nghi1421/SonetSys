<?php

declare(strict_types=1);

namespace App\Modules\Feed\Http\Requests;

use App\Modules\Feed\Application\DTOs\CreateCommentData;
use App\Modules\Feed\Domain\Models\Post;
use Illuminate\Foundation\Http\FormRequest;

final class CreateCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
            'parent_id' => ['sometimes', 'nullable', 'integer', 'exists:comments,id'],
        ];
    }

    public function toDto(): CreateCommentData
    {
        $user = $this->user();
        /** @var Post $post */
        $post = $this->route('post');

        return new CreateCommentData(
            body: (string) $this->validated('body'),
            postId: $post->id,
            parentId: $this->input('parent_id') !== null ? (int) $this->input('parent_id') : null,
            authorId: (int) $user->id,
        );
    }
}
