<?php

declare(strict_types=1);

namespace App\Core\Auth\Http\Requests;

use App\Core\Settings\Application\Services\SystemSettingApplier;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateProfileRequest extends FormRequest
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
        $maxUploadKb = $this->settings->maxUploadKb();

        return [
            'avatar' => ['sometimes', 'nullable', 'file', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:'.$maxUploadKb],
            'cover' => ['sometimes', 'nullable', 'file', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:'.$maxUploadKb],
            'remove_avatar' => ['sometimes', 'boolean'],
            'remove_cover' => ['sometimes', 'boolean'],
        ];
    }
}
