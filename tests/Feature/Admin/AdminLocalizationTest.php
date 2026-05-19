<?php

declare(strict_types=1);

use App\Enums\ApplicationStatus;
use App\Enums\DocumentVerificationStatus;
use App\Enums\EmploymentType;
use App\Enums\ExamInterviewType;
use App\Enums\Gender;
use App\Enums\NotificationType;
use App\Enums\ScreeningDecision;
use App\Enums\UserStatus;
use App\Enums\VacancyStatus;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

// ──────────────────────────────
// Locale switch
// ──────────────────────────────

test('admin locale switch stores session locale', function () {
    $this->get(route('lang.switch', 'am'))
        ->assertRedirect();

    expect(session('locale'))->toBe('am');
});

test('admin locale switch to english stores session locale', function () {
    $this->get(route('lang.switch', 'en'))
        ->assertRedirect();

    expect(session('locale'))->toBe('en');
});

test('authenticated admin preferred_locale is updated when locale is switched', function () {
    $admin = User::factory()->admin()->create(['preferred_locale' => 'en']);

    $this->actingAs($admin)->get(route('lang.switch', 'am'));

    expect($admin->fresh()->preferred_locale)->toBe('am');
});

test('invalid locale is rejected', function () {
    $this->get(route('lang.switch', 'xx'))
        ->assertRedirect();

    expect(session('locale'))->not->toBe('xx');
});

// ──────────────────────────────
// Enum label localization (English)
// ──────────────────────────────

test('vacancy status labels are localized in english', function () {
    app()->setLocale('en');

    expect(VacancyStatus::Open->label())->toBe('Open');
    expect(VacancyStatus::Draft->label())->toBe('Draft');
    expect(VacancyStatus::Closed->label())->toBe('Closed');
});

test('application status labels are localized in english', function () {
    app()->setLocale('en');

    expect(ApplicationStatus::Submitted->label())->toBe('Submitted');
    expect(ApplicationStatus::PassedScreening->label())->toBe('Passed Initial Screening');
    expect(ApplicationStatus::Selected->label())->toBe('Selected');
});

test('screening decision labels are localized in english', function () {
    app()->setLocale('en');

    expect(ScreeningDecision::Passed->label())->toBe('Passed');
    expect(ScreeningDecision::Failed->label())->toBe('Failed');
    expect(ScreeningDecision::Pending->label())->toBe('Pending');
});

test('document verification status labels are localized in english', function () {
    app()->setLocale('en');

    expect(DocumentVerificationStatus::Verified->label())->toBe('Verified');
    expect(DocumentVerificationStatus::Rejected->label())->toBe('Rejected');
    expect(DocumentVerificationStatus::Pending->label())->toBe('Pending');
});

test('user status labels are localized in english', function () {
    app()->setLocale('en');

    expect(UserStatus::Active->label())->toBe('Active');
    expect(UserStatus::Inactive->label())->toBe('Inactive');
    expect(UserStatus::Suspended->label())->toBe('Suspended');
});

test('gender labels are localized in english', function () {
    app()->setLocale('en');

    expect(Gender::Male->label())->toBe('Male');
    expect(Gender::Female->label())->toBe('Female');
});

test('employment type labels are localized in english', function () {
    app()->setLocale('en');

    expect(EmploymentType::Permanent->label())->toBe('Permanent');
    expect(EmploymentType::Contract->label())->toBe('Contract');
    expect(EmploymentType::PartTime->label())->toBe('Part-Time');
});

test('exam interview type labels are localized in english', function () {
    app()->setLocale('en');

    expect(ExamInterviewType::Exam->label())->toBe('Exam');
    expect(ExamInterviewType::Interview->label())->toBe('Interview');
});

test('notification type labels are localized in english', function () {
    app()->setLocale('en');

    expect(NotificationType::ExamInvitation->label())->toBe('Exam Invitation');
    expect(NotificationType::General->label())->toBe('General');
});

// ──────────────────────────────
// Enum label localization (Amharic)
// ──────────────────────────────

test('vacancy status labels are localized in amharic', function () {
    app()->setLocale('am');

    expect(VacancyStatus::Open->label())->toBe('ክፍት');
    expect(VacancyStatus::Draft->label())->toBe('ረቂቅ');
    expect(VacancyStatus::Closed->label())->toBe('ተዘግቷል');
});

test('application status labels are localized in amharic', function () {
    app()->setLocale('am');

    expect(ApplicationStatus::Submitted->label())->toBe('ተልኳል');
    expect(ApplicationStatus::Selected->label())->toBe('ተምርጧል');
});

test('screening decision labels are localized in amharic', function () {
    app()->setLocale('am');

    expect(ScreeningDecision::Passed->label())->toBe('አልፏል');
    expect(ScreeningDecision::Failed->label())->toBe('አላለፈም');
    expect(ScreeningDecision::Pending->label())->toBe('በጥበቃ ላይ');
});

test('user status labels are localized in amharic', function () {
    app()->setLocale('am');

    expect(UserStatus::Active->label())->toBe('ንቁ');
    expect(UserStatus::Inactive->label())->toBe('ንቁ ያልሆነ');
});

test('gender labels are localized in amharic', function () {
    app()->setLocale('am');

    expect(Gender::Male->label())->toBe('ወንድ');
    expect(Gender::Female->label())->toBe('ሴት');
});

// ──────────────────────────────
// Enum method compliance (no Filament dependency)
// ──────────────────────────────

test('vacancy status has getLabel and getColor methods', function () {
    expect(VacancyStatus::Open->getLabel())->toBeString();
    expect(VacancyStatus::Open->getColor())->toBeString();
    expect(VacancyStatus::Open->getColor())->toBe('success');
});

test('application status has getLabel and getColor methods', function () {
    expect(ApplicationStatus::Submitted->getLabel())->toBeString();
    expect(ApplicationStatus::Submitted->getColor())->toBeString()->toBe('info');
});

test('screening decision has getLabel and getColor methods', function () {
    expect(ScreeningDecision::Passed->getLabel())->toBeString();
    expect(ScreeningDecision::Passed->getColor())->toBeString()->toBe('success');
});

// ──────────────────────────────
// Admin nav group translation keys
// ──────────────────────────────

test('admin nav group translation keys exist in english', function () {
    app()->setLocale('en');

    expect(__('admin.nav_group.recruitment'))->toBe('Recruitment');
    expect(__('admin.nav_group.applications'))->toBe('Applications');
    expect(__('admin.nav_group.screening'))->toBe('Screening');
    expect(__('admin.nav_group.access_control'))->toBe('Access Control');
    expect(__('admin.nav_group.system'))->toBe('System');
});

test('admin nav group translation keys exist in amharic', function () {
    app()->setLocale('am');

    expect(__('admin.nav_group.recruitment'))->toBe('ምልመላ');
    expect(__('admin.nav_group.applications'))->toBe('ማመልከቻዎች');
    expect(__('admin.nav_group.screening'))->toBe('ማጣሪያ');
    expect(__('admin.nav_group.access_control'))->toBe('የመዳረሻ ቁጥጥር');
    expect(__('admin.nav_group.system'))->toBe('ስርዓት');
});

// ──────────────────────────────
// Authorization still works after localization
// ──────────────────────────────

test('applicant still cannot access admin panel after localization changes', function () {
    app()->setLocale('am');

    $user = User::factory()->asApplicant()->create();
    $response = $this->actingAs($user)->get('/admin');

    expect($response->status())->not->toBe(200);
});

test('unauthorized admin menus remain hidden after localization', function () {
    $report_viewer = User::factory()->reportViewer()->create();

    expect($report_viewer->hasPermissionTo('vacancies.create'))->toBeFalse();
    expect($report_viewer->hasPermissionTo('users.create'))->toBeFalse();
    expect($report_viewer->hasPermissionTo('settings.manage'))->toBeFalse();
});
