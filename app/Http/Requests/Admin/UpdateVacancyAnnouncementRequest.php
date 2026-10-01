<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\RecruitmentStatus;
use App\Models\RecruitmentAnnouncement;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'exam_required' => ['sometimes', 'boolean'],
            'published_at' => ['nullable', 'date', Rule::when($this->input('_publish_mode') === 'schedule', ['required', 'after:now'])],
        ];
    }

    /**
     * Once published, the application period is owned by the deadline-extension
     * workflow: the edit form shows the dates read-only (and may omit them), and
     * any attempt to change them here is rejected rather than silently applied.
     */
    private function datesLocked(): bool
    {
        $announcement = $this->route('announcement');

        return $announcement instanceof RecruitmentAnnouncement
            && $announcement->lifecycleStatus() !== RecruitmentStatus::Draft;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->datesLocked()) {
                return;
            }

            /** @var RecruitmentAnnouncement $announcement */
            $announcement = $this->route('announcement');

            foreach (['opening_date', 'closing_date'] as $field) {
                $submitted = $this->input($field);
                $current = $announcement->{$field}?->toDateString();

                if (filled($submitted) && $current !== null && strtotime((string) $submitted) !== false
                    && date('Y-m-d', strtotime((string) $submitted)) !== $current) {
                    $validator->errors()->add($field, __('recruitment.errors.deadline_locked'));
                }
            }
        });
    }

    protected function prepareForValidation(): void
    {
        if ($this->datesLocked()) {
            /** @var RecruitmentAnnouncement $announcement */
            $announcement = $this->route('announcement');
            foreach (['opening_date', 'closing_date'] as $field) {
                if (! $this->filled($field) && $announcement->{$field} !== null) {
                    $this->merge([$field => $announcement->{$field}->toDateString()]);
                }
            }
        }

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
