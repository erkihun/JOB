<?php

declare(strict_types=1);

use App\Enums\VacancyStatus;
use App\Models\User;
use App\Models\Vacancy;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->admin()->create();
});

function vacancyFormPayload(array $overrides = []): array
{
    return array_replace_recursive([
        'code' => 'VAC-FORM-001',
        'title' => ['en' => 'Accountant', 'am' => 'ሂሳብ ባለሙያ'],
        'location' => ['en' => 'Addis Ababa'],
        'status' => 'draft',
        'number_of_positions' => 1,
        'announcement_id' => $overrides['announcement_id'] ?? \App\Models\RecruitmentAnnouncement::factory()->create()->id,
        'requirement_groups_submitted' => 1,
    ], $overrides);
}

test('create form has no duplicated or dead requirement fields', function (): void {
    $html = $this->actingAs($this->admin)->get(route('admin.vacancies.create'))->assertOk()->getContent();

    // Field of study / experience / education now live only inside the requirement options.
    expect($html)
        ->not->toContain('name="field_of_study"')
        ->not->toContain('name="minimum_experience"')
        ->not->toContain('name="education_level"')
        // Each bilingual field is rendered once, as a tabbed pair.
        ->and(substr_count($html, 'name="title[en]"'))->toBe(1)
        ->and(substr_count($html, 'name="qualification_requirements[en]"'))->toBe(1)
        ->and($html)->toContain(__('vacancies.other_requirements'))
        ->and($html)->toContain(__('vacancies.section_publishing'));
});

test('edit form renders with saved values', function (): void {
    $vacancy = Vacancy::factory()->create(['title' => ['en' => 'Data Analyst', 'am' => 'ዳታ ተንታኝ']]);

    $this->actingAs($this->admin)->get(route('admin.vacancies.edit', $vacancy))
        ->assertOk()
        ->assertSee('value="Data Analyst"', false)
        ->assertSee('ዳታ ተንታኝ');
});

test('field of study and minimum experience are derived from the requirement options', function (): void {
    $this->actingAs($this->admin)->post(route('admin.vacancies.store'), vacancyFormPayload([
        'requirement_groups' => [
            ['is_active' => 1, 'requirements' => [
                ['education_level' => 'degree', 'field_of_study' => 'Accounting, Finance', 'min_experience_years' => 3],
            ]],
            ['is_active' => 1, 'requirements' => [
                ['education_level' => 'diploma', 'field_of_study' => 'accounting', 'min_experience_years' => 5],
            ]],
        ],
    ]))->assertSessionHasNoErrors();

    $vacancy = Vacancy::where('title->en', 'Accountant')->firstOrFail();

    expect($vacancy->field_of_study)->toBe('Accounting, Finance')
        ->and($vacancy->minimum_experience)->toBe(3); // least experience that can qualify
});

test('opening a vacancy records when it was first published', function (): void {
    $this->actingAs($this->admin)->post(route('admin.vacancies.store'), vacancyFormPayload(['status' => 'draft']));
    $vacancy = Vacancy::where('title->en', 'Accountant')->firstOrFail();
    expect($vacancy->published_at)->toBeNull();

    $this->actingAs($this->admin)->put(route('admin.vacancies.update', $vacancy), vacancyFormPayload([
        'code' => $vacancy->code, 'status' => 'open', 'announcement_id' => $vacancy->announcement_id,
    ]))->assertSessionHasNoErrors();

    $firstPublished = $vacancy->refresh()->published_at;
    expect($vacancy->status)->toBe(VacancyStatus::Open)->and($firstPublished)->not->toBeNull();

    // Saving again keeps the original publish date.
    $this->travel(2)->days();
    $this->actingAs($this->admin)->put(route('admin.vacancies.update', $vacancy), vacancyFormPayload([
        'code' => $vacancy->code, 'status' => 'open', 'announcement_id' => $vacancy->announcement_id,
    ]));
    expect($vacancy->refresh()->published_at->equalTo($firstPublished))->toBeTrue();
});

test('invalid status and employment type are rejected', function (): void {
    $this->actingAs($this->admin)->post(route('admin.vacancies.store'), vacancyFormPayload([
        'status' => 'published-ish',
        'employment_type' => 'gig',
    ]))->assertSessionHasErrors(['status', 'employment_type']);
});
