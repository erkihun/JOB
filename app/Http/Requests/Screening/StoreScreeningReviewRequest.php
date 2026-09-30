<?php

declare(strict_types=1);

namespace App\Http\Requests\Screening;

use App\Enums\ScreeningDecision;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreScreeningReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermissionTo('screening.review');
    }

    public function rules(): array
    {
        $decision = $this->input('decision');

        return [
            // Screening has exactly two outcomes. (Older correction-required /
            // pending records remain valid in history; they just can't be chosen.)
            'decision' => ['required', Rule::in([ScreeningDecision::Passed->value, ScreeningDecision::Failed->value])],
            'remark' => [
                Rule::when(
                    $decision === ScreeningDecision::Failed->value,
                    ['required', 'string', 'min:10', 'max:2000'],
                    ['nullable', 'string', 'max:2000'],
                ),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'remark.required' => __('messages.fail_remark_required'),
        ];
    }
}
