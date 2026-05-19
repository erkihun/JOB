<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Applicant;
use App\Models\Application;
use App\Models\Setting;
use App\Models\Vacancy;
use Illuminate\Support\Str;

class CodeGeneratorService
{
    /**
     * Generate a unique application reference number.
     *
     * Strategy: attempt count-based sequence up to 5 times, appending a random
     * hex suffix on each retry to break collisions under concurrent load.
     * The UNIQUE constraint on applications.reference_number is the final guard.
     */
    public function forApplication(): string
    {
        return $this->makeUnique(
            format: Setting::get('codes.application.format', '{PREFIX}-{YEAR}-{SEQ}'),
            prefix: Setting::get('codes.application.prefix', 'APP'),
            padding: (int) Setting::get('codes.application.padding', 6),
            existsFn: fn (string $code): bool => Application::where('reference_number', $code)->exists(),
        );
    }

    public function forVacancy(): string
    {
        return $this->makeUnique(
            format: Setting::get('codes.vacancy.format', '{PREFIX}-{YEAR}-{SEQ}'),
            prefix: Setting::get('codes.vacancy.prefix', 'VAC'),
            padding: (int) Setting::get('codes.vacancy.padding', 4),
            existsFn: fn (string $code): bool => Vacancy::where('code', $code)->exists(),
        );
    }

    public function forApplicant(): string
    {
        return $this->makeUnique(
            format: Setting::get('codes.applicant.format', '{PREFIX}-{YEAR}-{SEQ}'),
            prefix: Setting::get('codes.applicant.prefix', 'APL'),
            padding: (int) Setting::get('codes.applicant.padding', 5),
            existsFn: fn (string $code): bool => Applicant::where('applicant_code', $code)->exists(),
        );
    }

    public function vacancyAutoGenerate(): bool
    {
        return (bool) Setting::get('codes.vacancy.auto', true);
    }

    public function preview(string $format, string $prefix, int $padding): string
    {
        return $this->make($format, $prefix, $padding, 0, '');
    }

    /**
     * Generate a code and retry with a random suffix if a collision is found.
     * The DB unique constraint provides the definitive race-condition guard;
     * this avoids unnecessary DB failures in the 99.9 % non-collision case.
     *
     * @param  callable(string): bool  $existsFn
     */
    private function makeUnique(string $format, string $prefix, int $padding, callable $existsFn): string
    {
        $base = Application::whereYear('created_at', now()->year)->count();
        $tries = 0;

        do {
            $suffix = $tries === 0 ? '' : '-'.strtoupper(Str::random(3));
            $code = $this->make($format, $prefix, $padding, $base + $tries, $suffix);
            $tries++;
        } while ($tries < 10 && $existsFn($code));

        return $code;
    }

    private function make(string $format, string $prefix, int $padding, int $count, string $suffix): string
    {
        return strtr($format, [
            '{PREFIX}' => strtoupper($prefix),
            '{YEAR}' => now()->format('Y'),
            '{YY}' => now()->format('y'),
            '{MONTH}' => now()->format('m'),
            '{DAY}' => now()->format('d'),
            '{SEQ}' => str_pad((string) ($count + 1), max(1, $padding), '0', STR_PAD_LEFT),
        ]).$suffix;
    }
}
