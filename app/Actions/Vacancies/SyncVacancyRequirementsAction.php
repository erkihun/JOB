<?php

declare(strict_types=1);

namespace App\Actions\Vacancies;

use App\Models\Vacancy;
use Illuminate\Support\Facades\DB;

/**
 * Replaces a vacancy's eligibility requirement options with the submitted ones.
 *
 * Input shape (from the admin vacancy form):
 *   [
 *     ['title' => ?, 'is_active' => bool, 'requirements' => [
 *         ['education_level' => ?, 'field_of_study' => ?, 'min_experience_years' => int, ...],
 *     ]],
 *   ]
 *
 * Blank requirement rows and options without any requirement are dropped.
 */
class SyncVacancyRequirementsAction
{
    private const CRITERIA = [
        'education_level', 'field_of_study', 'min_experience_years',
        'min_gpa', 'graduation_year_from', 'graduation_year_to', 'notes',
    ];

    /**
     * @param  array<int, array<string, mixed>>  $groups
     */
    public function handle(Vacancy $vacancy, array $groups): void
    {
        DB::transaction(function () use ($vacancy, $groups): void {
            $vacancy->requirementGroups()->delete(); // requirements cascade

            $sortOrder = 0;
            foreach ($groups as $group) {
                $requirements = collect($group['requirements'] ?? [])
                    ->map(fn (array $row) => $this->normalise($row))
                    ->filter(fn (array $row) => $this->hasAnyCriterion($row))
                    ->values();

                if ($requirements->isEmpty()) {
                    continue;
                }

                $created = $vacancy->requirementGroups()->create([
                    'title' => filled($group['title'] ?? null) ? trim($group['title']) : null,
                    'sort_order' => $sortOrder++,
                    'is_active' => (bool) ($group['is_active'] ?? true),
                ]);

                $created->requirements()->createMany($requirements->all());
            }

            $this->syncSummaryColumns($vacancy);
        });

        $vacancy->unsetRelation('requirementGroups');
    }

    /**
     * Keep the vacancy's legacy summary columns (used by search, filters, exports and
     * listings) derived from the requirement options, so the options are the single
     * place admins enter them:
     *  - field_of_study: every accepted field across active options
     *  - minimum_experience: the least experience that can qualify (lowest option)
     * A vacancy without options keeps whatever values it already had.
     */
    private function syncSummaryColumns(Vacancy $vacancy): void
    {
        $groups = $vacancy->requirementGroups()->where('is_active', true)->with('requirements')->get();

        if ($groups->isEmpty()) {
            return;
        }

        $fields = $groups->flatMap->requirements
            ->pluck('field_of_study')
            ->filter()
            ->flatMap(fn (string $f) => preg_split('/\s*[,;\/|]\s*/u', $f) ?: [])
            ->map(fn (string $f) => trim($f))
            ->filter()
            ->unique(fn (string $f) => mb_strtolower($f))
            ->implode(', ');

        $vacancy->update([
            'field_of_study' => $fields !== '' ? mb_substr($fields, 0, 255) : null,
            'minimum_experience' => $groups->map(fn ($g) => (int) $g->requirements->max('min_experience_years'))->min(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function normalise(array $row): array
    {
        $data = [];
        foreach (self::CRITERIA as $key) {
            $value = $row[$key] ?? null;
            $data[$key] = is_string($value) ? (trim($value) === '' ? null : trim($value)) : $value;
        }
        $data['min_experience_years'] = (int) ($data['min_experience_years'] ?? 0);

        // Accept a reversed range ("2024 – 2018") rather than rejecting the form.
        if (filled($data['graduation_year_from']) && filled($data['graduation_year_to'])
            && (int) $data['graduation_year_from'] > (int) $data['graduation_year_to']) {
            [$data['graduation_year_from'], $data['graduation_year_to']] = [$data['graduation_year_to'], $data['graduation_year_from']];
        }

        return $data;
    }

    /** A row that sets no rule at all (e.g. only "0 years") is ignored. "Degree + 0 years" still counts. */
    private function hasAnyCriterion(array $row): bool
    {
        return filled($row['education_level'])
            || filled($row['field_of_study'])
            || $row['min_experience_years'] > 0
            || filled($row['min_gpa'])
            || filled($row['graduation_year_from'])
            || filled($row['graduation_year_to']);
    }
}
