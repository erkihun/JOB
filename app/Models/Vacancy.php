<?php

namespace App\Models;

use App\Enums\EmploymentType;
use App\Enums\VacancyStatus;
use App\Models\Concerns\HasOrderedUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Vacancy extends Model
{
    use HasFactory, HasOrderedUuid, HasTranslations, SoftDeletes;

    public array $translatable = [
        'title',
        'location',
        'description',
        'qualification_requirements',
    ];

    protected $fillable = [
        'title',
        'code',
        'department',
        'employment_type',
        'location',
        'number_of_positions',
        'salary_grade',
        'description',
        'qualification_requirements',
        'field_of_study',
        'minimum_experience',
        'opening_date',
        'closing_date',
        'status',
        'published_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => VacancyStatus::class,
            'employment_type' => EmploymentType::class,
            'opening_date' => 'date',
            'closing_date' => 'date',
            'published_at' => 'datetime',
            'number_of_positions' => 'integer',
            'minimum_experience' => 'integer',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function requiredDocuments(): HasMany
    {
        return $this->hasMany(VacancyDocument::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ExamInterviewSchedule::class);
    }

    public function isOpen(): bool
    {
        return $this->status === VacancyStatus::Open
            && now()->lte($this->closing_date->endOfDay());
    }

    public function isPastDeadline(): bool
    {
        return now()->gt($this->closing_date->endOfDay());
    }

    public function canAcceptApplications(): bool
    {
        return $this->isOpen() && ! $this->isPastDeadline();
    }
}
