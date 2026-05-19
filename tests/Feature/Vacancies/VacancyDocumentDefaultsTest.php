<?php

use App\Models\Vacancy;
use App\Models\VacancyDocument;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('vacancy document defaults to max_size_mb of 2', function () {
    $vacancy = Vacancy::factory()->open()->create();

    $doc = VacancyDocument::create([
        'vacancy_id' => $vacancy->id,
        'document_name' => 'CV',
        'allowed_types' => ['pdf'],
        'is_required' => true,
    ]);

    expect($doc->max_size_mb)->toBe(2);
});

test('vacancy document can be created with custom max_size_mb', function () {
    $vacancy = Vacancy::factory()->open()->create();

    $doc = VacancyDocument::create([
        'vacancy_id' => $vacancy->id,
        'document_name' => 'Transcript',
        'allowed_types' => ['pdf', 'jpg'],
        'max_size_mb' => 5,
        'is_required' => false,
    ]);

    expect($doc->max_size_mb)->toBe(5);
});

test('vacancy has many required documents', function () {
    $vacancy = Vacancy::factory()->open()->create();

    VacancyDocument::create([
        'vacancy_id' => $vacancy->id,
        'document_name' => 'CV',
        'allowed_types' => ['pdf'],
        'is_required' => true,
    ]);

    VacancyDocument::create([
        'vacancy_id' => $vacancy->id,
        'document_name' => 'Degree Certificate',
        'allowed_types' => ['pdf', 'jpg', 'jpeg', 'png'],
        'is_required' => true,
    ]);

    expect($vacancy->requiredDocuments()->count())->toBe(2);
});
