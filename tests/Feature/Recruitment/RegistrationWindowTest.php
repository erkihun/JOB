<?php

declare(strict_types=1);

use App\Models\RecruitmentAnnouncement;
use App\Models\User;
use App\Services\Recruitment\RecruitmentTimelineService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/*
 * Applicant accounts can be created only while at least one published
 * recruitment announcement is open (opening_date <= today <= closing_date).
 * Registration is global; applying is announcement-specific.
 */

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('local');
    $this->travelTo('2026-10-01 12:00:00');
});

function windowRegistrationData(array $overrides = []): array
{
    return array_merge([
        'first_name' => 'Hanna', 'middle_name' => 'Girma', 'last_name' => 'Alemu',
        'gender' => 'female', 'date_of_birth' => '1996-04-02', 'nationality' => 'Ethiopian',
        'national_id' => fake()->unique()->numerify('################'),
        'disability_status' => '0',
        'phone' => '+2519'.fake()->unique()->numerify('########'),
        'email' => 'window_'.uniqid().'@example.com',
        'password' => 'Password@123', 'password_confirmation' => 'Password@123',
        'preferred_locale' => 'en',
        'documents' => UploadedFile::fake()->create('documents.pdf', 200, 'application/pdf'),
    ], $overrides);
}

test('registration is blocked server-side when no announcement is open', function (): void {
    $data = windowRegistrationData();

    $this->post(route('applicant.register'), $data)
        ->assertRedirect(route('applicant.register'))
        ->assertSessionHasErrors(['registration' => __('recruitment.errors.registration_closed')]);

    expect(User::where('email', $data['email'])->exists())->toBeFalse()
        ->and(app(RecruitmentTimelineService::class)->canRegisterApplicant())->toBeFalse();
});

test('the registration page stays reachable but explains that registration is closed', function (): void {
    RecruitmentAnnouncement::factory()->create(['opening_date' => '2026-10-20', 'closing_date' => '2026-11-10']);

    $this->get(route('applicant.register'))
        ->assertOk()
        ->assertSee(__('recruitment.errors.registration_closed'))
        ->assertSee(__('recruitment.applicant.registration_next', ['date' => et_date(Carbon::parse('2026-10-20'), 'M d, Y')]))
        ->assertDontSee('name="password"', false);
});

test('registration is allowed while at least one announcement is open', function (): void {
    RecruitmentAnnouncement::factory()->create(['opening_date' => '2026-09-25', 'closing_date' => '2026-10-15']);
    $data = windowRegistrationData();

    $this->get(route('applicant.register'))->assertOk()->assertSee('name="password"', false);
    $this->post(route('applicant.register'), $data)->assertRedirect(route('applicant.verify-email'));

    expect(User::where('email', $data['email'])->exists())->toBeTrue();
});

test('a closed announcement does not block registration while another one is open', function (): void {
    RecruitmentAnnouncement::factory()->create(['opening_date' => '2026-08-01', 'closing_date' => '2026-08-31']);
    RecruitmentAnnouncement::factory()->create(['opening_date' => '2026-09-28', 'closing_date' => '2026-10-05']);

    $data = windowRegistrationData();
    $this->post(route('applicant.register'), $data)->assertRedirect(route('applicant.verify-email'));

    expect(User::where('email', $data['email'])->exists())->toBeTrue();
});

test('draft, scheduled, upcoming and cancelled announcements do not open registration', function (array $attributes): void {
    RecruitmentAnnouncement::factory()->create($attributes + ['opening_date' => '2026-09-25', 'closing_date' => '2026-10-15']);

    expect(app(RecruitmentTimelineService::class)->canRegisterApplicant())->toBeFalse();
})->with([
    'draft' => [['status' => 'draft', 'published_at' => null]],
    'scheduled publication' => [['published_at' => '2026-10-02 09:00:00']],
    'upcoming' => [['opening_date' => '2026-10-03']],
    'cancelled' => [['status' => 'cancelled']],
    'closed' => [['opening_date' => '2026-09-01', 'closing_date' => '2026-09-30']],
]);

test('the whole closing day is open for registration in the configured timezone', function (): void {
    RecruitmentAnnouncement::factory()->create(['opening_date' => '2026-09-25', 'closing_date' => '2026-10-01']);
    $timeline = app(RecruitmentTimelineService::class);

    $this->travelTo('2026-10-01 23:59:59');
    expect($timeline->canRegisterApplicant())->toBeTrue();

    $this->travelTo('2026-10-02 00:00:00');
    expect($timeline->canRegisterApplicant())->toBeFalse();
});
