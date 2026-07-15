<?php

declare(strict_types=1);

namespace App\Modules\Menu\Http\Requests;

use App\Modules\Menu\Application\DTOs\UpdateStaticPageData;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateStaticPageRequest extends FormRequest
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

    public function toDto(): UpdateStaticPageData
    {
        return new UpdateStaticPageData(
            title: (string) $this->validated('title'),
            content: (string) $this->validated('content'),
        );
    }
}
