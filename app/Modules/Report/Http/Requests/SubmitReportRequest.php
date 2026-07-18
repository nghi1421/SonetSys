<?php

declare(strict_types=1);

namespace App\Modules\Report\Http\Requests;

use App\Modules\Report\Domain\Enums\ReportReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class SubmitReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', new Enum(ReportReason::class)],
            'details' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }

    public function reason(): ReportReason
    {
        return ReportReason::from((string) $this->validated('reason'));
    }

    public function details(): ?string
    {
        return $this->validated('details');
    }
}
