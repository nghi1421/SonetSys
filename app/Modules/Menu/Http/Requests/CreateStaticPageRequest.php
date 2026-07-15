<?php

declare(strict_types=1);

namespace App\Modules\Menu\Http\Requests;

use App\Modules\Menu\Application\DTOs\CreateStaticPageData;
use Illuminate\Foundation\Http\FormRequest;

final class CreateStaticPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:20000'],
        ];
    }

    public function toDto(): CreateStaticPageData
    {
        $user = $this->user();

        return new CreateStaticPageData(
            title: (string) $this->validated('title'),
            content: (string) $this->validated('content'),
            tenantId: (int) $user->tenant_id,
            authorId: (int) $user->id,
        );
    }
}
