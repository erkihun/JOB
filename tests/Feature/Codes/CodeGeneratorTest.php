<?php

declare(strict_types=1);

use App\Models\Applicant;
use App\Models\Application;
use App\Models\Setting;
use App\Models\Vacancy;
use App\Services\CodeGeneratorService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('each series numbers on from its own records, not from applications', function (): void {
    Application::factory()->count(3)->create();

    $codes = app(CodeGeneratorService::class);
    $vacancyCount = Vacancy::count();

    expect($codes->forVacancy())->toBe(sprintf('VAC-%d-%04d', now()->year, $vacancyCount + 1))
        ->and($codes->forVacancy())->toBe(sprintf('VAC-%d-%04d', now()->year, $vacancyCount + 2));
});

test('consecutive calls never repeat a number', function (): void {
    $codes = app(CodeGeneratorService::class);

    $generated = collect(range(1, 25))->map(fn () => $codes->forApplicant());

    expect($generated->unique())->toHaveCount(25)
        ->and($generated->every(fn ($c) => ! str_contains(substr($c, -4), '-')))->toBeTrue();
});

test('numbers already taken are skipped', function (): void {
    $year = now()->year;
    Vacancy::factory()->create(['code' => sprintf('VAC-%d-%04d', $year, Vacancy::count() + 1)]);
    Vacancy::factory()->create(['code' => sprintf('VAC-%d-%04d', $year, 99)]);

    $code = app(CodeGeneratorService::class)->forVacancy();

    expect(Vacancy::where('code', $code)->exists())->toBeFalse();
});

test('previewing the next vacancy number does not use it up', function (): void {
    $codes = app(CodeGeneratorService::class);

    $preview = $codes->peekVacancy();

    expect($codes->peekVacancy())->toBe($preview)
        ->and($codes->forVacancy())->toBe($preview);
});

test('new applicants get distinct codes', function (): void {
    $applicants = Applicant::factory()->count(5)->create();

    expect($applicants->pluck('applicant_code')->unique())->toHaveCount(5);
});

test('overlapping rules skip existing and reserved codes across all three series', function (): void {
    Vacancy::factory()->create(['code' => '001']);
    foreach (['application', 'vacancy', 'applicant'] as $series) {
        Setting::set("codes.{$series}.format", '{SEQ}');
        Setting::set("codes.{$series}.padding", 3, 'integer');
    }

    $codes = app(CodeGeneratorService::class);
    expect($codes->forApplication())->toBe('002')
        ->and($codes->peekVacancy())->toBe('003')
        ->and($codes->forVacancy())->toBe('003')
        ->and($codes->forApplicant())->toBe('004');
});

test('a long run of collisions advances the sequence within the configured format', function (): void {
    Setting::set('codes.application.format', 'REF-{SEQ}');
    Setting::set('codes.application.padding', 1, 'integer');
    foreach (array_chunk(range(1, 1000), 100) as $numbers) {
        DB::table('generated_codes')->insert(array_map(fn (int $n): array => ['code' => "REF-{$n}"], $numbers));
    }

    expect(app(CodeGeneratorService::class)->forApplication())->toBe('REF-1001');
});
