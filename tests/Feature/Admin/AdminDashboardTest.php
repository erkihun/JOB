<?php

declare(strict_types=1);

use App\Enums\ApplicationStatus;
use App\Enums\VacancyStatus;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Vacancy;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Role;

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

test('dashboard requires dashboard view permission', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/admin');

    expect($response->status())->not->toBe(200);
});

test('authorized admin can access dashboard', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk()
        ->assertSeeText(__('dashboard.title'));
});

test('unauthorized admin cannot access dashboard', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo('vacancies.view');

    $response = $this->actingAs($user)->get('/admin');

    expect($response->status())->not->toBe(200);
});

test('applicant cannot access admin dashboard', function (): void {
    $applicant = User::factory()->asApplicant()->create();

    $response = $this->actingAs($applicant)->get('/admin');

    expect($response->status())->not->toBe(200);
});

test('quick action buttons respect permissions', function (): void {
    $viewer = User::factory()->screeningOfficer()->create();

    $this->actingAs($viewer)
        ->get('/admin')
        ->assertOk()
        ->assertDontSeeText(__('dashboard.quick_actions.create_vacancy'));

    $creator = User::factory()->admin()->create();

    $this->actingAs($creator)
        ->get('/admin')
        ->assertOk()
        ->assertSeeText(__('dashboard.quick_actions.create_vacancy'));
});

test('navigation hides unauthorized menus', function (): void {
    $role = Role::create(['name' => 'dashboard_only']);
    $role->givePermissionTo('dashboard.view');
    $user = User::factory()->create();
    $user->assignRole($role);

    $this->actingAs($user)
        ->get('/admin')
        ->assertOk()
        ->assertSeeText(__('menus.dashboard'))
        ->assertDontSeeText(__('menus.users'))
        ->assertDontSeeText(__('menus.reports'));
});

test('audit widget hidden without audit view', function (): void {
    AuditLog::create([
        'action' => 'settings_changed',
        'module' => 'settings',
        'created_at' => now(),
    ]);

    $user = User::factory()->screeningOfficer()->create();

    $this->actingAs($user)
        ->get('/admin')
        ->assertOk()
        ->assertDontSeeText(__('dashboard.sections.recent_activity'))
        ->assertDontSeeText('Settings Changed');
});

test('sensitive applicant data hidden without sensitive permission', function (): void {
    $applicant = Applicant::factory()->create(['full_name' => 'Sensitive Person']);
    Application::factory()->create(['applicant_id' => $applicant->id]);

    $user = User::factory()->screeningOfficer()->create();

    $this->actingAs($user)
        ->get('/admin')
        ->assertOk()
        ->assertSeeText(__('dashboard.restricted'))
        ->assertDontSeeText('Sensitive Person');
});

test('dashboard renders amharic labels when locale is am', function (): void {
    $admin = User::factory()->admin()->create(['preferred_locale' => 'am']);

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk()
        ->assertSeeText(__('dashboard.title'))
        ->assertSeeText(__('dashboard.kpi.open_vacancies'));
});

test('dashboard renders english labels when locale is en', function (): void {
    $admin = User::factory()->admin()->create(['preferred_locale' => 'en']);

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk()
        ->assertSeeText('Recruitment Dashboard')
        ->assertSeeText('Open Vacancies');
});

test('kpi counts are correct', function (): void {
    $applicants = Applicant::factory()->count(3)->create();
    // Reuse existing applicants so total applicant count stays at 3
    Application::factory()->create(['applicant_id' => $applicants->get(0)->id]);
    Application::factory()->create(['applicant_id' => $applicants->get(1)->id]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk()
        ->assertSeeText(__('dashboard.kpi.total_applicants'))
        ->assertSeeText('3')
        ->assertSeeText(__('dashboard.kpi.total_applications'))
        ->assertSeeText('2');
});

test('pending screening count is correct', function (): void {
    Application::factory()->create(['status' => ApplicationStatus::Submitted]);
    Application::factory()->create(['status' => ApplicationStatus::UnderReview]);
    Application::factory()->create(['status' => ApplicationStatus::Selected]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk()
        ->assertSeeText(__('dashboard.kpi.pending_screening'))
        ->assertSeeText('2');
});

test('open vacancy count is correct', function (): void {
    Vacancy::factory()->count(2)->open()->create();
    Vacancy::factory()->create(['status' => VacancyStatus::Closed]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk()
        ->assertSeeText(__('dashboard.kpi.open_vacancies'))
        ->assertSeeText('2');
});

test('reports center requires reports view permission', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo('dashboard.view');

    $response = $this->actingAs($user)->get('/admin/reports-center');

    expect($response->status())->not->toBe(200);
});

test('authorized admin can access reports center', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/reports-center')
        ->assertOk()
        ->assertSeeText(__('admin.reports_center.title'));
});
