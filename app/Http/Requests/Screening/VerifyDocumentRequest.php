<?php

declare(strict_types=1);

namespace App\Http\Requests\Screening;

use App\Enums\DocumentVerificationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerifyDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermissionTo('screening.verify-documents');
    }

    public function rules(): array
    {
        $status = $this->input('verification_status');

        return [
            'verification_status' => ['required', Rule::enum(DocumentVerificationStatus::class)],
            'verification_remark' => [
                Rule::when(
                    $status === DocumentVerificationStatus::Rejected->value,
                    ['required', 'string', 'min:5', 'max:1000'],
                    ['nullable', 'string', 'max:1000'],
                ),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'verification_remark.required' => 'A remark is required when rejecting a document.',
        ];
    }
}
