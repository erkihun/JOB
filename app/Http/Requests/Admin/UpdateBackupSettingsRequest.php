<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Services\BackupService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Non-secret backup preferences only. Keys, credentials and disks come from
 * .env; any such field posted here is simply not part of the validated data.
 */
class UpdateBackupSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('backups.settings.manage');
    }

    protected function prepareForValidation(): void
    {
        // Unchecked checkboxes are absent from the request.
        $this->merge([
            'enabled' => $this->boolean('enabled'),
            'compress' => $this->boolean('compress'),
            'encrypt' => $this->boolean('encrypt'),
            'include_database' => $this->boolean('include_database'),
        ]);
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(BackupService::TYPES)],
            'enabled' => ['required', 'boolean'],
            'frequency' => ['required', Rule::in(BackupService::FREQUENCIES)],
            'time' => ['required', 'date_format:H:i'],
            'retention_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'max_copies' => ['required', 'integer', 'min:1', 'max:1000'],
            'destination' => ['required', Rule::in(BackupService::DESTINATIONS)],
            'compress' => ['required', 'boolean'],
            'encrypt' => ['required', 'boolean'],
            'include_database' => ['boolean'],
            'includes' => [Rule::requiredIf(fn () => $this->input('type') === 'documents'), 'array', 'min:1'],
            'includes.*' => ['string', 'distinct', Rule::in(BackupService::DOCUMENT_GROUPS)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $backups = app(BackupService::class);

            if ($this->input('destination') === 's3' && ! $backups->s3Available()) {
                $validator->errors()->add('destination', __('backups.s3_unavailable'));
            }
            if ($this->boolean('encrypt') && ! $backups->encryptionKeyConfigured()) {
                $validator->errors()->add('encrypt', __('backups.key_missing'));
            }
        });
    }

    public function attributes(): array
    {
        return [
            'frequency' => __('backups.frequency'),
            'time' => __('backups.time'),
            'retention_days' => __('backups.retention_days'),
            'max_copies' => __('backups.max_copies'),
            'destination' => __('backups.destination'),
            'includes' => __('backups.includes'),
        ];
    }
}
