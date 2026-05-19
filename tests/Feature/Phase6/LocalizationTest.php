<?php

declare(strict_types=1);

use App\Models\Applicant;
use App\Models\User;
use App\Models\Vacancy;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('locale switch stores locale in session', function (): void {
    $response = $this->get(route('lang.switch', 'am'));

    $response->assertRedirect();
    $this->assertEquals('am', session('locale'));
});

test('locale switch to english stores en in session', function (): void {
    $this->get(route('lang.switch', 'am'));
    $this->get(route('lang.switch', 'en'));

    $this->assertEquals('en', session('locale'));
});

test('locale switch updates authenticated user preferred locale', function (): void {
    $user = User::factory()->asApplicant()->create();
    Applicant::factory()->create(['user_id' => $user->id, 'preferred_locale' => 'en']);

    $this->actingAs($user)->get(route('lang.switch', 'am'));

    $this->assertEquals('am', $user->fresh()->preferred_locale);
});

test('locale switch updates authenticated applicant preferred locale', function (): void {
    $user = User::factory()->asApplicant()->create();
    $applicant = Applicant::factory()->create(['user_id' => $user->id, 'preferred_locale' => 'en']);

    $this->actingAs($user)->get(route('lang.switch', 'am'));

    $this->assertEquals('am', $applicant->fresh()->preferred_locale);
});

test('authenticated user preferred locale is applied to the app', function (): void {
    $user = User::factory()->asApplicant()->create(['preferred_locale' => 'am']);
    Applicant::factory()->create(['user_id' => $user->id, 'preferred_locale' => 'am']);

    $this->actingAs($user)->get('/');

    $this->assertEquals('am', app()->getLocale());
});

test('session locale is applied when user not authenticated', function (): void {
    $this->withSession(['locale' => 'am'])->get('/');

    $this->assertEquals('am', app()->getLocale());
});

test('invalid locale is rejected by lang switch', function (): void {
    $this->get(route('lang.switch', 'fr'));

    // 'fr' is not in available locales, session should not be set to 'fr'
    $this->assertNotEquals('fr', session('locale'));
});

test('vacancy title falls back to English when Amharic translation is missing', function (): void {
    $vacancy = Vacancy::factory()->open()->create([
        'title' => ['en' => 'English Title', 'am' => ''],
    ]);

    $enTitle = $vacancy->getTranslation('title', 'en', false);
    $amTitle = $vacancy->getTranslation('title', 'am', false);

    expect($enTitle)->toBe('English Title');
    // When am is empty, getTranslation with useFallbackLocale=true returns en
    expect($vacancy->getTranslation('title', 'am', true))->toBe('English Title');
});

test('vacancy with Amharic translation returns Amharic value', function (): void {
    $vacancy = Vacancy::factory()->open()->create([
        'title' => ['en' => 'English Title', 'am' => 'የአማርኛ ርዕስ'],
    ]);

    expect($vacancy->getTranslation('title', 'am', false))->toBe('የአማርኛ ርዕስ');
});
