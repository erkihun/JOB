<?php

declare(strict_types=1);

namespace App\Services\Eligibility;

use App\Models\VacancyRequirementGroup;

final readonly class EligibilityResult
{
    /**
     * @param  array<int, array{option: int, label: string, reasons: array<int, string>}>  $failedOptions
     */
    public function __construct(
        public bool $eligible,
        public bool $hasRules,
        public ?VacancyRequirementGroup $matchedGroup = null,
        public ?int $matchedOption = null,
        public array $failedOptions = [],
    ) {}

    /** One-line outcome suitable for a flash message or a badge. */
    public function message(): string
    {
        return $this->eligible
            ? __('vacancies.eligibility_meets_option')
            : __('vacancies.eligibility_meets_no_option');
    }

    /**
     * Full explanation of why the applicant is not eligible, option by option.
     */
    public function reasonText(): string
    {
        if ($this->eligible) {
            return $this->message();
        }

        $lines = [$this->message()];
        foreach ($this->failedOptions as $failure) {
            $lines[] = $failure['label'].': '.implode(' ', $failure['reasons']);
        }

        return implode("\n", $lines);
    }
}
