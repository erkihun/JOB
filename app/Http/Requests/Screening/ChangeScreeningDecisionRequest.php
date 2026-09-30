<?php

declare(strict_types=1);

namespace App\Http\Requests\Screening;

use App\Enums\ScreeningDecision;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeScreeningDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermissionTo('screening.reverse-decision');
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in([ScreeningDecision::Passed->value, ScreeningDecision::Failed->value])],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => __('messages.decision_change_reason_required'),
            'reason.min' => __('messages.decision_change_reason_required'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return parent::getRedirectUrl().'#screening-decision';
    }
}
