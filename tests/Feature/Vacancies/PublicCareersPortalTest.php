<?php

declare(strict_types=1);

use App\Models\RecruitmentAnnouncement;
use App\Models\Vacancy;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function openVacancyClosingIn(int $days, array $attributes = []): Vacancy
{
    return Vacancy::factory()->open()->create($attributes + [
        'announcement_id' => RecruitmentAnnouncement::factory()->state([
            'opening_date' => now()->subDays(10),
            'closing_date' => now()->addDays($days),
        ]),
    ]);
}

test('home shows closing-soon vacancies, departments and the latest list', function (): void {
    openVacancyClosingIn(3, ['title' => ['en' => 'Urgent Role', 'am' => ''], 'department' => 'ICT Directorate', 'number_of_positions' => 2]);
    openVacancyClosingIn(40, ['title' => ['en' => 'Later Role', 'am' => ''], 'department' => 'Finance', 'number_of_positions' => 5]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee(__('public.closing_soon'))
        ->assertSee('Urgent Role')
        ->assertSee('Later Role')
        ->assertSee(__('public.browse_by_department'))
        ->assertSee(route('vacancies.index', ['department' => 'ICT Directorate']), false)
        ->assertSee('name="reference_number"', false);
});

test('vacancies can be filtered by several departments and by closing window', function (): void {
    openVacancyClosingIn(3, ['title' => ['en' => 'IT Role', 'am' => ''], 'department' => 'IT']);
    openVacancyClosingIn(20, ['title' => ['en' => 'HR Role', 'am' => ''], 'department' => 'HR']);
    openVacancyClosingIn(20, ['title' => ['en' => 'Legal Role', 'am' => ''], 'department' => 'Legal']);

    $this->get(route('vacancies.index', ['department' => ['IT', 'HR']]))
        ->assertOk()->assertSee('IT Role')->assertSee('HR Role')->assertDontSee('Legal Role');

    $this->get(route('vacancies.index', ['closing_within' => 7]))
        ->assertOk()->assertSee('IT Role')->assertDontSee('HR Role');
});

test('vacancies can be sorted by closing date', function (): void {
    openVacancyClosingIn(30, ['title' => ['en' => 'Closes Last', 'am' => '']]);
    openVacancyClosingIn(2, ['title' => ['en' => 'Closes First', 'am' => '']]);

    $this->get(route('vacancies.index', ['sort' => 'closing']))
        ->assertOk()
        ->assertSeeInOrder(['Closes First', 'Closes Last']);
});

test('track page prefills the reference number from the home form', function (): void {
    $this->get(route('track.show', ['reference_number' => 'APP-2026-000123']))
        ->assertOk()
        ->assertSee('value="APP-2026-000123"', false);
});
