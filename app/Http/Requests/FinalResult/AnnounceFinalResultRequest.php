<?php

declare(strict_types=1);

namespace App\Http\Requests\FinalResult;

use Illuminate\Foundation\Http\FormRequest;

class AnnounceFinalResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasAnyRole(['super_admin', 'admin', 'hr_manager'])
            || (bool) $this->user()?->hasPermissionTo('notifications.send');
    }

    public function rules(): array
    {
        return [
            'application_ids' => ['required', 'array', 'min:1'],
            'application_ids.*' => ['required', 'uuid', 'exists:applications,id'],
            'channel' => ['required', 'in:in_system,email'],
            'message' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
