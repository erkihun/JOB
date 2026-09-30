<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVacancyAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'opening_date' => ['required', 'date'],
            'closing_date' => ['required', 'date', 'after:opening_date'],
            'code' => ['required', 'string', 'max:100', Rule::unique('recruitment_announcements', 'code')->ignore($this->route('announcement')?->id)],
            'institution_ids' => ['nullable', 'array'],
            'institution_ids.*' => ['uuid', 'distinct', Rule::exists('institutions', 'id')->whereNull('deleted_at')],
            'subject' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,published'],
            'published_at' => ['nullable', 'date', Rule::when($this->input('_publish_mode') === 'schedule', ['required', 'after:now'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        // The form sends _publish_mode (draft | now | schedule); only "schedule"
        // carries a publish date. Requests without it keep the previous behaviour.
        $mode = $this->input('_publish_mode');
        if ($mode !== null && $mode !== 'schedule') {
            $scheduledLater = $this->route('announcement')?->published_at?->isFuture() ?? false;
            $this->merge(['published_at' => $mode === 'now' && $scheduledLater ? now()->format('Y-m-d H:i:s') : null]);

            return;
        }

        if ($this->filled('_pub_date')) {
            $time = $this->input('_pub_time', '00:00') ?: '00:00';
            $this->merge(['published_at' => $this->input('_pub_date').' '.$time.':00']);
        }
    }
}
