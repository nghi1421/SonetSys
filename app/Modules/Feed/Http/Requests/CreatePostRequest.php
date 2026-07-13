<?php

declare(strict_types=1);

namespace App\Modules\Feed\Http\Requests;

use App\Modules\Feed\Application\DTOs\CreatePostData;
use App\Modules\Feed\Domain\Enums\PostVisibility;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
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
            'body' => ['required', 'string', 'max:10000'],
            'visibility' => ['sometimes', new Enum(PostVisibility::class)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->user()?->tenant_id === null) {
                $validator->errors()->add('tenant', 'Only tenant members can create posts.');
            }
        });
    }

    public function toDto(): CreatePostData
    {
        $user = $this->user();

        return new CreatePostData(
            body: (string) $this->validated('body'),
            visibility: $this->has('visibility')
                ? PostVisibility::from((string) $this->validated('visibility'))
                : PostVisibility::TenantOnly,
            tenantId: (int) $user->tenant_id,
            authorId: (int) $user->id,
        );
    }
}
