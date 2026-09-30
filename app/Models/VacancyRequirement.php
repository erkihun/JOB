<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EducationLevel;
use App\Models\Concerns\HasOrderedUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A set of criteria that must all be met. Empty criteria are not checked.
 */
class VacancyRequirement extends Model
{
    use HasOrderedUuid;

    protected $attributes = [
        'min_experience_years' => 0,
    ];

    protected $fillable = [
        'vacancy_requirement_group_id',
        'education_level',
        'field_of_study',
        'min_experience_years',
        'min_gpa',
        'graduation_year_from',
        'graduation_year_to',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'education_level' => EducationLevel::class,
            'min_experience_years' => 'integer',
            'min_gpa' => 'decimal:2',
            'graduation_year_from' => 'integer',
            'graduation_year_to' => 'integer',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(VacancyRequirementGroup::class, 'vacancy_requirement_group_id');
    }

    /**
     * Short human-readable summary, e.g. "Degree · Computer Science · 2 years experience".
     */
    public function summary(): string
    {
        $parts = [];

        $parts[] = $this->education_level?->getLabel() ?? __('vacancies.any_education_level');

        if (filled($this->field_of_study)) {
            $parts[] = $this->field_of_study;
        }

        $parts[] = trans_choice('vacancies.experience_years_count', $this->min_experience_years, ['count' => $this->min_experience_years]);

        if ($this->min_gpa !== null) {
            $parts[] = __('vacancies.min_gpa_value', ['gpa' => number_format((float) $this->min_gpa, 2)]);
        }

        if ($this->graduation_year_from || $this->graduation_year_to) {
            $parts[] = __('vacancies.graduated_between', [
                'from' => $this->graduation_year_from ?? '…',
                'to' => $this->graduation_year_to ?? '…',
            ]);
        }

        return implode(' · ', $parts);
    }
}
