<?php

declare(strict_types=1);

namespace App\Http\Requests\Screening;

use Illuminate\Foundation\Http\FormRequest;

class AssignReviewerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermissionTo('applications.assign-reviewer');
    }

    public function rules(): array
    {
        return [
            'reviewer_id' => ['nullable', 'uuid', 'exists:users,id'],
        ];
    }
}
