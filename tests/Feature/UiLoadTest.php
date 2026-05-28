<?php

declare(strict_types=1);

use App\Models\Applicant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('public home page loads', function (): void {
    $this->get('/')->assertOk();
});

it('public home page exposes a search-first vacancy hero', function (): void {
    $this->get('/')
        ->assertOk()
        ->assertSee('name="search"', false)
        ->assertSee(__('public.search_jobs'));
});

it('amharic public layout uses the locale font marker', function (): void {
    $this->withSession(['locale' => 'am'])
        ->get('/')
        ->assertOk()
        ->assertSee('locale-am', false)
        ->assertDontSee('Abyssinica', false);
});

it('vacancy listing page loads', function (): void {
    $this->get('/vacancies')->assertOk();
});

it('applicant login page loads', function (): void {
    $this->get('/applicant/login')->assertOk();
});

it('applicant register page loads', function (): void {
    $this->get('/applicant/register')->assertOk();
});

it('applicant dashboard loads when logged in', function (): void {
    $user = User::factory()->asApplicant()->create();

    $this->actingAs($user)->get('/applicant/dashboard')->assertOk();
});

it('my applications page loads when logged in', function (): void {
    $applicant = Applicant::factory()->create();

    $this->actingAs($applicant->user)
        ->get('/applicant/applications')
        ->assertOk();
});

it('applicant dashboard redirects when guest', function (): void {
    $this->get('/applicant/dashboard')->assertRedirect();
});

it('my applications page redirects when guest', function (): void {
    $this->get('/applicant/applications')->assertRedirect();
});
