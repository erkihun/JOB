<?php

declare(strict_types=1);

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Admin\ReportsController;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->admin()->create();
});

test('reports center shows KPIs, breakdowns and the report library', function (): void {
    Application::factory()->count(3)->create(['status' => ApplicationStatus::PassedScreening]);
    Application::factory()->create(['status' => ApplicationStatus::FailedScreening]);
    Application::factory()->create(['status' => ApplicationStatus::Submitted]);

    $this->actingAs($this->admin)->get(route('admin.reports.index'))
        ->assertOk()
        ->assertSee(__('messages.report_pipeline'))
        ->assertSee(__('messages.report_gender'))
        ->assertSee(__('messages.report_by_vacancy'))
        ->assertSee(__('messages.report_library'))
        ->assertSee('75%')   // 3 passed of 4 screened
        ->assertSee(route('admin.reports.export', ['report' => 'audit-log']), false);
});

test('filters narrow the numbers and show removable chips', function (): void {
    Application::factory()->count(2)->create(['status' => ApplicationStatus::PassedScreening]);
    Application::factory()->create(['status' => ApplicationStatus::FailedScreening]);

    $this->actingAs($this->admin)->get(route('admin.reports.index', ['status' => 'failed_screening']))
        ->assertOk()
        ->assertSee(__('messages.report_showing'))
        ->assertViewHas('total', 1);

    // Junk filter values are ignored instead of erroring
    $this->actingAs($this->admin)->get(route('admin.reports.index', ['status' => 'nope', 'date_from' => 'garbage']))
        ->assertOk()
        ->assertViewHas('total', 3);
});

test('every report in the library downloads as Excel and is audited', function (string $report): void {
    Application::factory()->count(2)->create(['status' => ApplicationStatus::PassedScreening]);

    $this->actingAs($this->admin)->get(route('admin.reports.export', ['report' => $report, 'date_from' => now()->subYear()->toDateString()]))
        ->assertOk()
        ->assertHeader('content-disposition');

    expect(AuditLog::where('action', 'report_exported')->where('new_values->report', $report)->exists())->toBeTrue();
})->with(array_keys(ReportsController::REPORTS));

test('unknown reports 404 and missing permissions 403', function (): void {
    $this->actingAs($this->admin)->get('/admin/reports/export/not-a-report')->assertNotFound();

    // Exam officer: can view reports but has no export permission
    $examOfficer = User::factory()->create();
    $examOfficer->assignRole('exam_officer');
    $this->actingAs($examOfficer)->get(route('admin.reports.index'))
        ->assertOk()
        ->assertDontSee(__('messages.report_library'));
    $this->actingAs($examOfficer)->get(route('admin.reports.export', ['report' => 'applicants']))->assertForbidden();

    // Report viewer: can export, but not the audit log
    $viewer = User::factory()->create();
    $viewer->assignRole('report_viewer');
    $this->actingAs($viewer)->get(route('admin.reports.export', ['report' => 'audit-log']))->assertForbidden();
    $this->actingAs($viewer)->get(route('admin.reports.index'))
        ->assertOk()
        ->assertDontSee(route('admin.reports.export', ['report' => 'audit-log']), false);
});
