<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Enums\ScreeningDecision;
use App\Models\Concerns\HasOrderedUuid;
use App\Services\CodeGeneratorService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Application extends Model
{
    use HasFactory, HasOrderedUuid, SoftDeletes;

    protected $fillable = [
        'applicant_id',
        'vacancy_id',
        'reference_number',
        'field_of_study',
        'graduation_date',
        'cgpa',
        'status',
        'submitted_at',
        'last_updated_at',
        'locked_at',
        'screening_status',
        'screening_remark',
        'screened_by',
        'screened_at',
        'assigned_reviewer_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'screening_status' => ScreeningDecision::class,
            'graduation_date' => 'date',
            'submitted_at' => 'datetime',
            'last_updated_at' => 'datetime',
            'locked_at' => 'datetime',
            'screened_at' => 'datetime',
            'cgpa' => 'decimal:2',
        ];
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(Vacancy::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class);
    }

    public function screeningReviews(): HasMany
    {
        return $this->hasMany(ScreeningReview::class);
    }

    public function screener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'screened_by');
    }

    public function assignedReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_reviewer_id');
    }

    public function examInterviewApplicants(): HasMany
    {
        return $this->hasMany(ExamInterviewApplicant::class);
    }

    public function finalResult(): HasOne
    {
        return $this->hasOne(FinalResult::class);
    }

    public function isEditable(): bool
    {
        if ($this->locked_at !== null) {
            return false;
        }

        if ($this->hasFinalScreeningDecision()) {
            return false;
        }

        return $this->vacancy->canAcceptApplications();
    }

    public function isLocked(): bool
    {
        return $this->locked_at !== null
            || $this->hasFinalScreeningDecision()
            || $this->vacancy->isPastDeadline();
    }

    public function hasFinalScreeningDecision(): bool
    {
        return in_array($this->status, [
            ApplicationStatus::PassedScreening,
            ApplicationStatus::FailedScreening,
        ], true);
    }

    protected static function booted(): void
    {
        static::creating(function (Application $application) {
            if (empty($application->reference_number)) {
                $application->reference_number = app(CodeGeneratorService::class)->forApplication();
            }
            $application->submitted_at ??= now();
        });
    }
}
