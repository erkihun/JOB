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

it('unified login page loads', function (): void {
    $this->get('/login')->assertOk();
});

it('legacy applicant login page redirects to unified login', function (): void {
    $this->get('/applicant/login')->assertRedirect(route('login'));
});

it('applicant register page loads', function (): void {
    $this->get('/applicant/register')->assertOk();
});

it('applicant dashboard loads when logged in', function (): void {
    $user = User::factory()->asApplicant()->create();

    $this->actingAs($user)->get('/applicant/dashboard')->assertOk();
});

it('public layout sends authenticated admins back to the admin dashboard', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/')
        ->assertOk()
        ->assertSee(route('admin.dashboard'), false);
});

it('public layout keeps applicant dashboard links for applicant users', function (): void {
    $user = User::factory()->asApplicant()->create();

    $this->actingAs($user)
        ->get('/')
        ->assertOk()
        ->assertSee(route('applicant.dashboard'), false);
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
