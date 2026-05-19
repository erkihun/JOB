<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EducationLevel;
use App\Enums\Gender;
use App\Models\Concerns\HasOrderedUuid;
use App\Services\CodeGeneratorService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Applicant extends Model
{
    use HasFactory, HasOrderedUuid;

    protected $fillable = [
        'user_id',
        'applicant_code',
        // Name
        'first_name',
        'middle_name',
        'last_name',
        'full_name',
        // Contact
        'phone',
        'alternative_phone',
        'email',
        // Personal
        'national_id',
        'gender',
        'date_of_birth',
        'nationality',
        // Disability
        'disability_status',
        'disability_type',
        // Education
        'ethnicity',
        'university_name',
        'field_of_study',
        'graduation_year',
        'graduation_date',
        'gpa',
        'education_level',
        // Work experience
        'work_experience_years',
        'work_experience_months',
        'current_employer',
        'current_position',
        'work_experience_summary',
        // Address
        'address',
        // Preferences
        'preferred_locale',
        'profile_photo_path',
    ];

    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'education_level' => EducationLevel::class,
            'disability_status' => 'boolean',
            'date_of_birth' => 'date',
            'graduation_date' => 'date',
            'gpa' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Applicant $applicant) {
            if (empty($applicant->applicant_code)) {
                $applicant->applicant_code = app(CodeGeneratorService::class)->forApplicant();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(ApplicantNotification::class);
    }

    public function profileDocuments(): HasMany
    {
        return $this->hasMany(ApplicantProfileDocument::class);
    }

    public function hasAppliedTo(Vacancy $vacancy): bool
    {
        return $this->applications()->where('vacancy_id', $vacancy->id)->exists();
    }

    /**
     * Percentage (0-100) of how complete the profile is.
     * Based on 10 equally-weighted key fields.
     */
    public function profileCompletionPercentage(): int
    {
        $checks = [
            ! empty($this->first_name) && ! empty($this->last_name),
            ! empty($this->national_id),
            ! empty($this->gender),
            ! empty($this->date_of_birth),
            ! empty($this->phone),
            ! empty($this->university_name) && ! empty($this->field_of_study),
            ! empty($this->graduation_year),
            ! empty($this->address),
            ! empty($this->profile_photo_path),
            $this->profileDocuments()->where('document_type', 'documents')->exists(),
        ];

        $filled = count(array_filter($checks));

        return (int) round(($filled / count($checks)) * 100);
    }

    /**
     * Returns translated labels of fields that are still missing.
     *
     * @return list<string>
     */
    public function profileMissingFields(): array
    {
        $missing = [];

        if (empty($this->first_name) || empty($this->last_name)) {
            $missing[] = __('fields.full_name');
        }
        if (empty($this->national_id)) {
            $missing[] = __('fields.national_id');
        }
        if (empty($this->gender)) {
            $missing[] = __('fields.gender');
        }
        if (empty($this->date_of_birth)) {
            $missing[] = __('fields.date_of_birth');
        }
        if (empty($this->phone)) {
            $missing[] = __('fields.phone');
        }
        if (empty($this->university_name) || empty($this->field_of_study)) {
            $missing[] = __('fields.university_name');
        }
        if (empty($this->graduation_year)) {
            $missing[] = __('fields.graduation_year');
        }
        if (empty($this->address)) {
            $missing[] = __('fields.address');
        }
        if (empty($this->profile_photo_path)) {
            $missing[] = __('fields.profile_photo');
        }
        if (! $this->profileDocuments()->where('document_type', 'documents')->exists()) {
            $missing[] = __('documents.type_documents');
        }

        return $missing;
    }
}
