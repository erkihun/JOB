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
            'decision' => ['required', Rule::enum(ScreeningDecision::class)],
            'remark' => [
                Rule::when(
                    in_array($decision, [ScreeningDecision::Failed->value, ScreeningDecision::CorrectionRequired->value], true),
                    ['required', 'string', 'min:10', 'max:2000'],
                    ['nullable', 'string', 'max:2000'],
                ),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'remark.required' => 'A remark is required when marking an application as failed or correction required.',
        ];
    }
}
