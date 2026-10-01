<?php

declare(strict_types=1);

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\FinalResult;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->admin()->create();
});

// ── Screening: only pass or fail ─────────────────────────────────────────────

test('screening form offers only pass and fail', function (): void {
    $application = Application::factory()->afterDeadline()->create(['status' => ApplicationStatus::Submitted]);

    $html = $this->actingAs($this->admin)->get(route('admin.screening.review', $application))->assertOk()->getContent();

    expect($html)->toContain('name="decision" value="passed"')
        ->toContain('name="decision" value="failed"')
        ->not->toContain('value="correction_required"');
});

test('screening rejects decisions other than pass or fail', function (string $decision): void {
    $application = Application::factory()->afterDeadline()->create(['status' => ApplicationStatus::Submitted]);

    $this->actingAs($this->admin)
        ->post(route('admin.screening.submit', $application), ['decision' => $decision, 'remark' => 'Some remark text here'])
        ->assertSessionHasErrors('decision');

    expect($application->refresh()->status)->toBe(ApplicationStatus::Submitted);
})->with(['correction_required', 'pending']);

test('failing an application requires a remark', function (): void {
    $application = Application::factory()->afterDeadline()->create(['status' => ApplicationStatus::Submitted]);

    $this->actingAs($this->admin)
        ->post(route('admin.screening.submit', $application), ['decision' => 'failed'])
        ->assertSessionHasErrors('remark');

    $this->actingAs($this->admin)
        ->post(route('admin.screening.submit', $application), ['decision' => 'passed'])
        ->assertSessionHasNoErrors();

    expect($application->refresh()->status)->toBe(ApplicationStatus::PassedScreening);
});

// ── Result weights: exam + interview + practical ─────────────────────────────

test('final score includes the practical test', function (): void {
    // 50% exam, 30% interview, 20% practical
    expect(FinalResult::computeFinalScore(80, 70, 50, 30, 90, 20))->toBe(79.0)
        // Practical weight 0 behaves exactly like before
        ->and(FinalResult::computeFinalScore(80, 70, 60, 40))->toBe(76.0)
        // A missing practical score is left out and the rest scaled up
        ->and(FinalResult::computeFinalScore(80, 70, 50, 30, null, 20))->toBe(76.25);
});

test('settings save three weights that must total 100', function (): void {
    $this->actingAs($this->admin)->put(route('admin.settings.update'), [
        '_section' => 'results',
        'results' => ['exam_weight' => 50, 'interview_weight' => 30, 'practical_weight' => 20],
    ])->assertSessionHasNoErrors();

    expect((float) Setting::get('results.practical_weight'))->toBe(20.0);

    $this->actingAs($this->admin)->put(route('admin.settings.update'), [
        '_section' => 'results',
        'results' => ['exam_weight' => 50, 'interview_weight' => 30, 'practical_weight' => 30],
    ])->assertSessionHasErrors('results.exam_weight');
});

test('final result stores the practical score and weight', function (): void {
    $application = Application::factory()->create(['status' => ApplicationStatus::InterviewCompleted]);

    $this->actingAs($this->admin)->post(route('admin.final-results.store', $application), [
        'exam_score' => 80, 'interview_score' => 70, 'practical_score' => 90,
        'exam_weight' => 50, 'interview_weight' => 30, 'practical_weight' => 20,
        'decision' => 'selected',
    ])->assertSessionHasNoErrors();

    $result = $application->refresh()->finalResult;
    expect((float) $result->practical_score)->toBe(90.0)
        ->and((float) $result->practical_weight)->toBe(20.0)
        ->and((float) $result->final_score)->toBe(79.0);

    $this->actingAs($this->admin)->get(route('admin.final-results.create', $application))
        ->assertOk()
        ->assertSee('name="practical_score"', false)
        ->assertSee(__('messages.practical_weight'));
});

// ── Breadcrumb is replaced during in-app navigation ──────────────────────────

test('top bar breadcrumb is swapped on fast navigation', function (): void {
    $this->actingAs($this->admin)->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('data-admin-breadcrumb', false)
        ->assertSee('swapBreadcrumb(nextDocument)', false);
});
