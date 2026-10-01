<?php

declare(strict_types=1);

use App\Actions\Announcements\ExtendRecruitmentDeadlineAction;
use App\Enums\ApplicationStatus;
use App\Enums\NotificationType;
use App\Exceptions\RecruitmentRuleException;
use App\Jobs\NotifyDeadlineExtensionJob;
use App\Models\ApplicantNotification;
use App\Models\AuditLog;
use App\Models\RecruitmentDeadlineExtension;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->travelTo('2026-10-01 12:00:00');
    $this->admin = User::factory()->admin()->create();
});

function extensionPayload(array $overrides = []): array
{
    return $overrides + [
        'new_closing_date' => '2026-10-20',
        'reason' => 'Low number of applicants from the regions.',
        'reference' => 'HR/123/2026',
        'confirm' => '1',
    ];
}

test('a user without the extend-deadline permission cannot extend', function (): void {
    $vacancy = lifecycleVacancy('2026-09-20', '2026-10-10');
    $officer = User::factory()->screeningOfficer()->create();

    $this->actingAs($officer)->post(route('admin.announcements.extend-deadline', $vacancy->announcement), extensionPayload())
        ->assertForbidden();

    expect(fn () => app(ExtendRecruitmentDeadlineAction::class)->handle($vacancy->announcement, $officer, '2026-10-20', 'Valid reason text'))
        ->toThrow(AuthorizationException::class);
    expect($vacancy->announcement->refresh()->closing_date->toDateString())->toBe('2026-10-10');
});

test('the normal edit form cannot silently change a published deadline', function (): void {
    $vacancy = lifecycleVacancy('2026-09-20', '2026-10-10');
    $announcement = $vacancy->announcement;

    $this->actingAs($this->admin)->put(route('admin.announcements.update', $announcement), [
        'subject' => $announcement->subject, 'code' => $announcement->code, 'status' => 'published',
        'opening_date' => '2026-09-20', 'closing_date' => '2026-12-31',
        'institution_ids' => $announcement->institutions->modelKeys(),
    ])->assertSessionHasErrors(['closing_date' => __('recruitment.errors.deadline_locked')]);

    // Omitting the (read-only) dates keeps them; other fields still save.
    $this->put(route('admin.announcements.update', $announcement), [
        'subject' => 'Renamed recruitment', 'code' => $announcement->code, 'status' => 'published',
        'institution_ids' => $announcement->institutions->modelKeys(),
    ])->assertSessionHasNoErrors();

    expect($announcement->refresh()->closing_date->toDateString())->toBe('2026-10-10')
        ->and($announcement->subject)->toBe('Renamed recruitment')
        ->and(RecruitmentDeadlineExtension::count())->toBe(0);
});

test('an extension requires a reason and confirmation', function (): void {
    $vacancy = lifecycleVacancy('2026-09-20', '2026-10-10');

    $this->actingAs($this->admin)
        ->post(route('admin.announcements.extend-deadline', $vacancy->announcement), extensionPayload(['reason' => '', 'confirm' => null]))
        ->assertSessionHasErrors(['reason', 'confirm']);

    expect(RecruitmentDeadlineExtension::count())->toBe(0);
});

test('the new deadline must be later than the current one', function (string $date): void {
    $vacancy = lifecycleVacancy('2026-09-20', '2026-10-10');

    $this->actingAs($this->admin)
        ->post(route('admin.announcements.extend-deadline', $vacancy->announcement), extensionPayload(['new_closing_date' => $date]))
        ->assertSessionHasErrors(['new_closing_date' => __('recruitment.errors.extension_not_later')]);

    expect($vacancy->announcement->refresh()->closing_date->toDateString())->toBe('2026-10-10');
})->with(['same day' => ['2026-10-10'], 'earlier' => ['2026-10-05']]);

test('an extension records history and audit, keeps applications, and notifies applicants through the queue', function (): void {
    Queue::fake();
    $vacancy = lifecycleVacancy('2026-09-01', '2026-09-28');
    $announcement = $vacancy->announcement;
    $application = lifecycleApplication($vacancy, ApplicationStatus::Submitted);
    $announcement->forceFill(['status' => 'closed', 'closed_at' => now()->subDays(3)])->save();

    $this->actingAs($this->admin)->post(route('admin.announcements.extend-deadline', $announcement), extensionPayload())
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.announcements.show', $announcement));

    $extension = RecruitmentDeadlineExtension::sole();
    expect($extension->old_closing_date->toDateString())->toBe('2026-09-28')
        ->and($extension->new_closing_date->toDateString())->toBe('2026-10-20')
        ->and($extension->reason)->toBe('Low number of applicants from the regions.')
        ->and($extension->extended_by)->toBe($this->admin->id)
        ->and($extension->notified_count)->toBe(1);

    // Closed → accepting again until the new date; the submitted application is untouched.
    expect($announcement->refresh()->status)->toBe('published')
        ->and($announcement->closing_date->toDateString())->toBe('2026-10-20')
        ->and($vacancy->refresh()->canAcceptApplications())->toBeTrue()
        ->and($application->refresh()->status)->toBe(ApplicationStatus::Submitted)
        ->and($application->isEditable())->toBeTrue();

    $audit = AuditLog::where('action', 'deadline_extended')->sole();
    expect($audit->old_values)->toBe(['closing_date' => '2026-09-28'])
        ->and($audit->new_values['closing_date'])->toBe('2026-10-20')
        ->and($audit->new_values['reason'])->toBe('Low number of applicants from the regions.')
        ->and(AuditLog::where('action', 'announcement_reopened')->exists())->toBeTrue();

    Queue::assertPushed(NotifyDeadlineExtensionJob::class, fn ($job) => $job->extensionId === $extension->id);

    // Running the job creates the queued, audited notification.
    app()->call([new NotifyDeadlineExtensionJob($extension->id), 'handle']);
    $notification = ApplicantNotification::where('applicant_id', $application->applicant_id)->sole();
    expect($notification->type)->toBe(NotificationType::DeadlineExtended)
        ->and($notification->message)->toContain('2026-09-28')->toContain('2026-10-20')
        ->and(AuditLog::where('action', 'deadline_extension_notifications_dispatched')->exists())->toBeTrue();
});

test('history is immutable', function (): void {
    $vacancy = lifecycleVacancy('2026-09-20', '2026-10-10');
    $extension = app(ExtendRecruitmentDeadlineAction::class)
        ->handle($vacancy->announcement, $this->admin, '2026-10-15', 'Allow late regional postings');

    expect(fn () => $extension->update(['reason' => 'rewritten']))->toThrow(LogicException::class)
        ->and(fn () => $extension->delete())->toThrow(LogicException::class);
});

test('an extension is blocked once screening has started', function (): void {
    $vacancy = lifecycleVacancy('2026-09-01', '2026-09-28');
    lifecycleApplication($vacancy, ApplicationStatus::PassedScreening);

    $this->actingAs($this->admin)->post(route('admin.announcements.extend-deadline', $vacancy->announcement), extensionPayload())
        ->assertSessionHasErrors(['new_closing_date' => __('recruitment.errors.extension_after_assessment')]);

    expect(RecruitmentDeadlineExtension::count())->toBe(0);
});

test('an extension is blocked in the exam stage and for drafts', function (string $status): void {
    $vacancy = lifecycleVacancy('2026-09-01', '2026-09-28', ['status' => $status]);

    expect(fn () => app(ExtendRecruitmentDeadlineAction::class)->handle($vacancy->announcement, $this->admin, '2026-10-20', 'Late regional postings'))
        ->toThrow(RecruitmentRuleException::class);
})->with(['exam', 'screening', 'draft', 'finalized']);
