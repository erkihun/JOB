<?php

declare(strict_types=1);

use App\Enums\ApplicationStatus;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->admin()->create();
});

test('every main admin page uses the shared page header', function (string $route): void {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super_admin');

    $this->actingAs($superAdmin)->get(route($route))
        ->assertOk()
        ->assertSee('class="page-header', false)
        ->assertSee('class="page-title"', false);
})->with([
    'admin.dashboard', 'admin.institutions.index', 'admin.vacancies.index', 'admin.announcements.index',
    'admin.applications.index', 'admin.applicants.index', 'admin.screening.index', 'admin.screening.passed',
    'admin.screening.failed', 'admin.schedules.index', 'admin.schedule-results.index', 'admin.final-results.index',
    'admin.notification-templates.index', 'admin.reports.index', 'admin.users.index', 'admin.roles.index',
    'admin.settings.index', 'admin.hero-sliders.index', 'admin.audit-logs.index',
]);

test('application stage tabs filter the list and show counts', function (): void {
    Application::factory()->count(2)->create(['status' => ApplicationStatus::Submitted]);
    Application::factory()->create(['status' => ApplicationStatus::FailedScreening]);

    $this->actingAs($this->admin)->get(route('admin.applications.index'))
        ->assertOk()
        ->assertViewHas('stageCounts', fn ($c) => $c['all'] === 3 && $c['awaiting'] === 2 && $c['failed'] === 1);

    $this->actingAs($this->admin)->get(route('admin.applications.index', ['stage' => 'failed']))
        ->assertOk()
        ->assertViewHas('applications', fn ($p) => $p->total() === 1);
});

test('applicant pages hide personal data without the sensitive permission', function (): void {
    $applicant = Applicant::factory()->create(['email' => 'hidden.person@example.com', 'national_id' => '1234567890123456']);

    $officer = User::factory()->screeningOfficer()->create();
    $officer->givePermissionTo('applications.view');
    $officer->revokePermissionTo('applications.view-sensitive');

    $this->actingAs($officer)->get(route('admin.applicants.show', $applicant))
        ->assertOk()
        ->assertDontSee('hidden.person@example.com')
        ->assertDontSee('1234567890123456')
        ->assertSee(__('dashboard.restricted'));

    $this->actingAs($this->admin)->get(route('admin.applicants.show', $applicant))
        ->assertOk()
        ->assertSee('hidden.person@example.com');
});

test('audit log filters by user and date, and shows change details', function (): void {
    AuditLog::create(['user_id' => $this->admin->id, 'action' => 'profile_updated', 'module' => 'users',
        'new_values' => ['fields' => ['name']], 'created_at' => now()]);
    AuditLog::create(['user_id' => null, 'action' => 'old_event', 'module' => 'system', 'ip_address' => '10.9.9.9', 'created_at' => now()->subYear()]);

    $this->actingAs($this->admin)->get(route('admin.audit-logs.index', ['date_from' => now()->subDay()->toDateString()]))
        ->assertOk()
        ->assertSee('Profile Updated')
        ->assertDontSee('10.9.9.9')
        ->assertSee(__('messages.audit_details'));

    $this->actingAs($this->admin)->get(route('admin.audit-logs.index', ['search' => 'nobody-matches']))
        ->assertOk()
        ->assertSee(__('messages.empty_filtered'));
});
