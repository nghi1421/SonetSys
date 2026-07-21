<?php

declare(strict_types=1);

namespace App\Modules\Feed\Http\Requests;

use App\Core\Settings\Application\Services\SystemSettingApplier;
use App\Modules\Feed\Application\DTOs\UpdateReactionTypeData;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateReactionTypeRequest extends FormRequest
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
            'label' => ['required', 'string', 'max:64'],
            'emoji' => ['nullable', 'string', 'max:16'],
            'icon' => ['nullable', 'file', 'image', 'max:'.$this->settings->maxUploadKb()],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function toDto(): UpdateReactionTypeData
    {
        return new UpdateReactionTypeData(
            label: (string) $this->validated('label'),
            emoji: $this->filled('emoji') ? (string) $this->validated('emoji') : null,
            icon: $this->file('icon'),
            sortOrder: (int) ($this->validated('sort_order') ?? 0),
        );
    }
}
