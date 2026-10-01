<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ExtendRecruitmentDeadlineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('recruitment-announcements.extend-deadline');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'new_closing_date' => ['required', 'date_format:Y-m-d'],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
            'reference' => ['nullable', 'string', 'max:255'],
            'confirm' => ['accepted'],
        ];
    }

    public function attributes(): array
    {
        return [
            'new_closing_date' => __('recruitment.extension.new_deadline'),
            'reason' => __('recruitment.reason'),
            'reference' => __('recruitment.extension.reference'),
            'confirm' => __('recruitment.extension.confirm_label'),
        ];
    }
}
