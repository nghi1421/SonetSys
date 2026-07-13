<?php

declare(strict_types=1);

namespace App\Modules\Feed\Http\Requests;

use App\Modules\Feed\Application\DTOs\UpdatePostData;
use App\Modules\Feed\Domain\Enums\PostVisibility;
use App\Modules\Feed\Domain\Models\Post;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class UpdatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:10000'],
            'visibility' => ['sometimes', new Enum(PostVisibility::class)],
        ];
    }

    public function toDto(Post $post): UpdatePostData
    {
        return new UpdatePostData(
            body: (string) $this->validated('body'),
            visibility: $this->has('visibility')
                ? PostVisibility::from((string) $this->validated('visibility'))
                : $post->visibility,
        );
    }
}
