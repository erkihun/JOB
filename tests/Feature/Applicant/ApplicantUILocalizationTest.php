<?php

declare(strict_types=1);

use App\Models\Applicant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed(RolesAndPermissionsSeeder::class);
});

// ──────────────────────────────
// Lang file existence
// ──────────────────────────────

test('public lang file exists for english', function (): void {
    app()->setLocale('en');

    expect(__('public.home'))->toBe('Home');
    expect(__('public.open_vacancies'))->toBe('Open Vacancies');
    expect(__('public.browse_vacancies'))->toBe('Browse Vacancies');
    expect(__('public.how_it_works'))->toBe('How to Apply');
});

test('public lang file exists for amharic', function (): void {
    app()->setLocale('am');

    expect(__('public.home'))->toBe('መነሻ');
    expect(__('public.open_vacancies'))->toBe('ክፍት የሥራ ቦታዎች');
    expect(__('public.browse_vacancies'))->toBe('ክፍት ቦታዎችን ይመልከቱ');
    expect(__('public.how_it_works'))->toBe('እንዴት ማመልከቻ ማቅረብ ይቻላል');
});

test('applicant lang file exists for english', function (): void {
    app()->setLocale('en');

    expect(__('applicant.welcome', ['name' => 'Test']))->toBe('Welcome, Test');
    expect(__('applicant.my_applications'))->toBe('My Applications');
    expect(__('applicant.sign_in'))->toBe('Sign in to your account');
    expect(__('applicant.register_heading'))->toBe('Create your applicant account');
});

test('applicant lang file exists for amharic', function (): void {
    app()->setLocale('am');

    expect(__('applicant.welcome', ['name' => 'ፈልቃ']))->toBe('እንኳን ደህና መጡ፣ ፈልቃ');
    expect(__('applicant.my_applications'))->toBe('ማመልከቻዎቼ');
    expect(__('applicant.sign_in'))->toBe('ወደ መለያዎ ይግቡ');
});

test('documents lang file exists for english', function (): void {
    app()->setLocale('en');

    expect(__('documents.upload'))->toBe('Upload Document');
    expect(__('documents.replace'))->toBe('Replace Document');
    expect(__('documents.verified'))->toBe('Verified');
    expect(__('documents.max_size'))->toBe('Max');
});

test('documents lang file exists for amharic', function (): void {
    app()->setLocale('am');

    expect(__('documents.upload'))->toBe('ሰነድ ስቀሉ');
    expect(__('documents.verified'))->toBe('ተረጋግጧል');
});

test('messages lang file exists for english', function (): void {
    app()->setLocale('en');

    expect(__('messages.application_submitted'))->toBe('Your application has been submitted successfully.');
    expect(__('messages.profile_updated'))->toBe('Your profile has been updated.');
});

test('messages lang file exists for amharic', function (): void {
    app()->setLocale('am');

    expect(__('messages.application_submitted'))->toBe('ማመልከቻዎ በተሳካ ሁኔታ ቀርቧል።');
});

// ──────────────────────────────
// Menus lang file has new keys
// ──────────────────────────────

test('menus lang has home and login keys in english', function (): void {
    app()->setLocale('en');

    expect(__('menus.home'))->toBe('Home');
    expect(__('menus.login'))->toBe('Login');
    expect(__('menus.register'))->toBe('Register');
});

test('menus lang has home and login keys in amharic', function (): void {
    app()->setLocale('am');

    expect(__('menus.home'))->toBe('መነሻ');
    expect(__('menus.login'))->toBe('ይግቡ');
    expect(__('menus.register'))->toBe('ይመዝገቡ');
});

// ──────────────────────────────
// Public pages render in both locales
// ──────────────────────────────

test('home page renders in english', function (): void {
    app()->setLocale('en');

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee('How to Apply');
});

test('home page renders in amharic after locale switch', function (): void {
    $this->get(route('lang.switch', 'am'));

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee('እንዴት ማመልከቻ ማቅረብ ይቻላል');
});

test('vacancies index renders in english', function (): void {
    $response = $this->get(route('vacancies.index'));

    $response->assertOk();
    $response->assertSee(__('vacancies.job_vacancies'));
});

// ──────────────────────────────
// Auth pages render
// ──────────────────────────────

test('login page renders in english', function (): void {
    $response = $this->get(route('login'));

    $response->assertOk();
    $response->assertSee(__('auth.unified_login'));
});

test('login page renders in amharic', function (): void {
    $this->get(route('lang.switch', 'am'));
    $response = $this->get(route('login'));

    $response->assertOk();
    $response->assertSee(__('auth.unified_login'));
});

test('register page renders in english', function (): void {
    $response = $this->get(route('applicant.register'));

    $response->assertOk();
    $response->assertSee('Create your applicant account');
});

// ──────────────────────────────
// Applicant panel pages render
// ──────────────────────────────

test('applicant dashboard renders in english', function (): void {
    $user = User::factory()->asApplicant()->create();
    Applicant::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get(route('applicant.dashboard'));

    $response->assertOk();
    $response->assertSee('Welcome');
});

test('applicant dashboard renders in amharic', function (): void {
    $user = User::factory()->asApplicant()->create(['preferred_locale' => 'am']);
    Applicant::factory()->create(['user_id' => $user->id, 'preferred_locale' => 'am']);

    $response = $this->actingAs($user)->get(route('applicant.dashboard'));

    $response->assertOk();
    $response->assertSee('እንኳን ደህና');
});

test('applicant applications index renders', function (): void {
    $user = User::factory()->asApplicant()->create();
    Applicant::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get(route('applicant.applications.index'));

    $response->assertOk();
    $response->assertSee(__('applicant.my_applications'));
});

test('applicant notifications page renders', function (): void {
    $user = User::factory()->asApplicant()->create();
    Applicant::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get(route('applicant.notifications.index'));

    $response->assertOk();
    $response->assertSee(__('applicant.notifications_heading'));
});

test('applicant profile show page renders', function (): void {
    $user = User::factory()->asApplicant()->create();
    Applicant::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get(route('applicant.profile.show'));

    $response->assertOk();
    $response->assertSee(__('applicant.profile_heading'));
});

// ──────────────────────────────
// Authorization is not weakened
// ──────────────────────────────

test('unauthenticated user cannot access applicant dashboard', function (): void {
    $response = $this->get(route('applicant.dashboard'));

    $response->assertRedirect();
});

test('applicant cannot access admin panel', function (): void {
    $user = User::factory()->asApplicant()->create();

    $response = $this->actingAs($user)->get('/admin');

    expect($response->status())->not->toBe(200);
});

test('public vacancy pages are accessible without login', function (): void {
    $response = $this->get(route('vacancies.index'));
    $response->assertOk();

    $response = $this->get(route('home'));
    $response->assertOk();
});
