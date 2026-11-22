<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Applicant;
use App\Models\Application;
use App\Models\Setting;
use App\Models\Vacancy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use UnexpectedValueException;

/**
 * Human-readable numbers for applications, vacancies and applicants.
 *
 * Each series ("application", "vacancy", "applicant", per year when the format
 * contains the year) has its own counter row in `code_sequences`. The row is
 * locked (SELECT … FOR UPDATE) while it is incremented, so concurrent requests
 * always get different numbers. The UNIQUE column constraints stay as the last
 * line of defence.
 */
class CodeGeneratorService
{
    private const SERIES = [
        'application' => ['model' => Application::class, 'column' => 'reference_number', 'prefix' => 'APP', 'padding' => 6],
        'vacancy' => ['model' => Vacancy::class, 'column' => 'code', 'prefix' => 'VAC', 'padding' => 4],
        'applicant' => ['model' => Applicant::class, 'column' => 'applicant_code', 'prefix' => 'APL', 'padding' => 5],
    ];

    public function forApplication(): string
    {
        return $this->next('application');
    }

    public function forVacancy(): string
    {
        return $this->next('vacancy');
    }

    public function forApplicant(): string
    {
        return $this->next('applicant');
    }

    /** The number the next vacancy is likely to get, without using it up. */
    public function peekVacancy(): string
    {
        [$format, $prefix, $padding] = $this->settingsFor('vacancy');
        $current = DB::table('code_sequences')->where('key', $this->sequenceKey('vacancy', $format))->value('last_value')
            ?? $this->initialValue('vacancy', $format);

        do {
            $code = $this->make($format, $prefix, $padding, ++$current);
        } while ($this->alreadyAssigned($code) || DB::table('generated_codes')->where('code', $code)->exists());

        return $code;
    }

    public function vacancyAutoGenerate(): bool
    {
        return (bool) Setting::get('codes.vacancy.auto', true);
    }

    public function preview(string $format, string $prefix, int $padding): string
    {
        return $this->make($format, $prefix, $padding, 1);
    }

    private function next(string $series): string
    {
        [$format, $prefix, $padding] = $this->settingsFor($series);
        $key = $this->sequenceKey($series, $format);

        return DB::transaction(function () use ($series, $format, $prefix, $padding, $key): string {
            // Create the counter once, starting after the records that already exist.
            DB::table('code_sequences')->insertOrIgnore([
                'key' => $key,
                'last_value' => $this->initialValue($series, $format),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $value = (int) DB::table('code_sequences')->where('key', $key)->lockForUpdate()->value('last_value');

            // Skip numbers already taken (e.g. entered by hand or imported).
            do {
                $code = $this->make($format, $prefix, $padding, ++$value);
            } while ($this->alreadyAssigned($code) || DB::table('generated_codes')->insertOrIgnore(['code' => $code]) === 0);

            DB::table('code_sequences')->where('key', $key)->update(['last_value' => $value, 'updated_at' => now()]);

            return $code;
        }, attempts: 3);
    }

    /** @return array{0: string, 1: string, 2: int} */
    private function settingsFor(string $series): array
    {
        $defaults = self::SERIES[$series];
        $format = (string) Setting::get("codes.{$series}.format", '{PREFIX}-{YEAR}-{SEQ}');
        if (! str_contains($format, '{SEQ}')) {
            throw new UnexpectedValueException("The {$series} code format must contain {SEQ}.");
        }

        return [
            $format,
            (string) Setting::get("codes.{$series}.prefix", $defaults['prefix']),
            (int) Setting::get("codes.{$series}.padding", $defaults['padding']),
        ];
    }

    /** Numbering restarts each year only when the format shows the year. */
    private function sequenceKey(string $series, string $format): string
    {
        return str_contains($format, '{YEAR}') || str_contains($format, '{YY}')
            ? $series.':'.now()->format('Y')
            : $series;
    }

    /** Starting point for a brand-new counter: the records already in this series. */
    private function initialValue(string $series, string $format): int
    {
        /** @var class-string<Model> $model */
        $model = self::SERIES[$series]['model'];
        $query = $model::query()->withoutGlobalScopes();

        if ($this->sequenceKey($series, $format) !== $series) {
            $query->whereYear('created_at', now()->year);
        }

        return $query->count();
    }

    private function alreadyAssigned(string $code): bool
    {
        foreach (self::SERIES as ['model' => $model, 'column' => $column]) {
            if ($model::query()->withoutGlobalScopes()->where($column, $code)->exists()) {
                return true;
            }
        }

        return false;
    }

    private function make(string $format, string $prefix, int $padding, int $number): string
    {
        return strtr($format, [
            '{PREFIX}' => strtoupper($prefix),
            '{YEAR}' => now()->format('Y'),
            '{YY}' => now()->format('y'),
            '{MONTH}' => now()->format('m'),
            '{DAY}' => now()->format('d'),
            '{SEQ}' => str_pad((string) $number, max(1, $padding), '0', STR_PAD_LEFT),
        ]);
    }
}
