<?php

declare(strict_types=1);

namespace App\Http\Requests\FinalResult;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'channel' => ['required', Rule::in(Setting::get('sms.enabled', false) ? ['in_system', 'email', 'sms'] : ['in_system', 'email'])],
            'message' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
