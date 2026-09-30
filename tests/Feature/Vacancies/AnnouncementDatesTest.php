<?php

declare(strict_types=1);

use App\Actions\Applications\SubmitApplicationAction;
use App\Actions\Applications\UpdateApplicationAction;
use App\Exports\VacancyWiseApplicantReportExport;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\Institution;
use App\Models\RecruitmentAnnouncement;
use App\Models\User;
use App\Models\Vacancy;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->travelTo(now()->setDate(2026, 9, 15)->setTime(12, 0));
});

function announcementApplicationData(): array
{
    return ['field_of_study' => 'Computer Science', 'graduation_date' => '2020-07-01', 'cgpa' => 3.5];
}

function announcementFormData(RecruitmentAnnouncement $announcement): array
{
    return [
        'subject' => $announcement->subject, 'code' => $announcement->code,
        'opening_date' => $announcement->opening_date->toDateString(),
        'closing_date' => $announcement->closing_date->toDateString(),
        'status' => $announcement->status, 'institution_ids' => $announcement->institutions->modelKeys(),
    ];
}

test('multiple vacancies share one announcement period and edits apply to all of them', function (): void {
    $announcement = RecruitmentAnnouncement::factory()->create();
    $vacancies = Vacancy::factory()->count(2)->for($announcement, 'announcement')->create();
    expect(Schema::hasColumn('vacancies', 'opening_date'))->toBeFalse()
        ->and(Schema::hasColumn('vacancies', 'closing_date'))->toBeFalse();
    foreach ($vacancies as $vacancy) {
        expect($vacancy->announcement->opening_date->equalTo($announcement->opening_date))->toBeTrue()
            ->and($vacancy->canAcceptApplications())->toBeTrue();
    }
    $payload = announcementFormData($announcement->refresh());
    $payload['closing_date'] = today()->subDay()->toDateString();
    $payload['opening_date'] = today()->subDays(10)->toDateString();
    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.announcements.update', $announcement), $payload)->assertSessionHasNoErrors();
    foreach ($vacancies as $vacancy) {
        expect($vacancy->refresh()->canAcceptApplications())->toBeFalse()
            ->and($vacancy->announcement->closing_date->toDateString())->toBe($payload['closing_date']);
    }
});

test('admin saves vacancies without individual dates and ignores submitted date overrides', function (): void {
    $announcement = RecruitmentAnnouncement::factory()->create();
    $admin = User::factory()->admin()->create();
    $payload = [
        'announcement_id' => $announcement->id, 'code' => 'SHARED-ONE',
        'title' => ['en' => 'First Position'], 'location' => ['en' => 'Addis Ababa'],
        'number_of_positions' => 1, 'status' => 'open',
    ];
    $this->actingAs($admin)->post(route('admin.vacancies.store'), $payload)->assertSessionHasNoErrors();
    $payload['code'] = 'SHARED-TWO';
    $payload['opening_date'] = '1990-01-01';
    $payload['closing_date'] = '1990-02-01';
    $this->post(route('admin.vacancies.store'), $payload)->assertSessionHasNoErrors();
    expect($announcement->vacancies()->count())->toBe(2)
        ->and($announcement->refresh()->opening_date->toDateString())->toBe('2026-09-14');
    $vacancy = $announcement->vacancies()->first();
    foreach ([route('admin.vacancies.create', ['announcement_id' => $announcement->id]), route('admin.vacancies.edit', $vacancy)] as $url) {
        $this->get($url)->assertOk()->assertDontSee('name="opening_date"', false)
            ->assertDontSee('name="closing_date"', false)
            ->assertSee(et_date($announcement->opening_date, 'M d, Y'))
            ->assertSee(et_date($announcement->closing_date, 'M d, Y'));
    }
});

test('announcement dates and code are required and closing must be strictly later', function (): void {
    $this->actingAs(User::factory()->admin()->create());
    $this->post(route('admin.announcements.store'), ['subject' => 'Recruitment', 'status' => 'draft'])
        ->assertSessionHasErrors(['opening_date', 'closing_date', 'code']);
    $this->post(route('admin.announcements.store'), [
        'subject' => 'Recruitment', 'code' => 'ANN-VALIDATE', 'status' => 'published',
        'opening_date' => '2026-09-15', 'closing_date' => '2026-09-15',
    ])->assertSessionHasErrors('closing_date');
});

test('applications respect both announcement boundaries including the entire closing day', function (string $instant, bool $allowed): void {
    $announcement = RecruitmentAnnouncement::factory()->create([
        'opening_date' => '2026-09-10', 'closing_date' => '2026-09-20', 'published_at' => '2026-09-01',
    ]);
    $vacancy = Vacancy::factory()->for($announcement, 'announcement')->create();
    $applicant = Applicant::factory()->create();
    $this->travelTo(Carbon::parse($instant));
    expect($vacancy->canAcceptApplications())->toBe($allowed);
    $response = $this->actingAs($applicant->user)
        ->post(route('applicant.applications.store', $vacancy), announcementApplicationData());
    $response->assertRedirect();
    expect(Application::where('vacancy_id', $vacancy->id)->exists())->toBe($allowed);
    if (! $allowed) {
        expect(fn () => app(SubmitApplicationAction::class)->handle($applicant, $vacancy, announcementApplicationData()))
            ->toThrow(ValidationException::class);
    }
})->with([
    'before opening' => ['2026-09-09 23:59:59', false],
    'opening midnight' => ['2026-09-10 00:00:00', true],
    'during period' => ['2026-09-15 12:00:00', true],
    'last second of closing day' => ['2026-09-20 23:59:59', true],
    'after closing' => ['2026-09-21 00:00:00', false],
]);

test('editing is blocked after parent closes through the endpoint and action', function (): void {
    $vacancy = Vacancy::factory()->create();
    $applicant = Applicant::factory()->create();
    $application = app(SubmitApplicationAction::class)->handle($applicant, $vacancy, announcementApplicationData());
    $vacancy->announcement->update(['opening_date' => today()->subDays(10), 'closing_date' => today()->subDay()]);
    $this->actingAs($applicant->user)->put(route('applicant.applications.update', $application), announcementApplicationData())->assertForbidden();
    expect(fn () => app(UpdateApplicationAction::class)->handle($application, announcementApplicationData()))->toThrow(ValidationException::class);
    expect($application->fresh()->isLocked())->toBeTrue();
});

test('draft scheduled and archived announcements cannot accept applications', function (string $state): void {
    $vacancy = Vacancy::factory()->create();
    match ($state) {
        'draft' => $vacancy->announcement->update(['status' => 'draft']),
        'scheduled' => $vacancy->announcement->update(['published_at' => now()->addDay()]),
        'archived' => $vacancy->announcement->delete(),
    };
    expect($vacancy->refresh()->canAcceptApplications())->toBeFalse()
        ->and(Vacancy::acceptingApplications()->count())->toBe(0);
    $this->get(route('vacancies.show', $vacancy))->assertNotFound();
})->with(['draft', 'scheduled', 'archived']);

test('public cards detail filters and dashboard use announcement dates', function (): void {
    $vacancy = Vacancy::factory()->create(['title' => ['en' => 'Shared Date Position']]);
    foreach ([route('vacancies.index'), route('vacancies.show', $vacancy), route('announcements.show', $vacancy->announcement)] as $url) {
        $this->get($url)->assertOk()->assertSee(et_date($vacancy->announcement->opening_date, 'M d, Y'))
            ->assertSee(et_date($vacancy->announcement->closing_date, 'M d, Y'));
    }
    $this->get(route('vacancies.index', ['opening_date' => '2026-09-15']))->assertDontSee('Shared Date Position');
    $this->get(route('vacancies.index', ['closing_date' => '2026-09-16']))->assertDontSee('Shared Date Position');
    $this->actingAs(User::factory()->admin()->create())->get(route('admin.dashboard'))
        ->assertOk()->assertViewHas('stats', fn ($stats) => $stats['open_vacancies'] === 1);
    $vacancy->announcement->update(['opening_date' => today()->addDay()]);
    $this->get(route('admin.dashboard'))->assertViewHas('stats', fn ($stats) => $stats['open_vacancies'] === 0);
});

test('announcement institutions cannot be bypassed or removed while in use', function (): void {
    $vacancy = Vacancy::factory()->create();
    $announcement = $vacancy->announcement;
    $this->actingAs(User::factory()->admin()->create());
    $payload = [
        'announcement_id' => $announcement->id, 'institution_id' => Institution::factory()->create()->id,
        'code' => 'OUTSIDE', 'title' => ['en' => 'Outside'], 'location' => ['en' => 'Addis Ababa'],
        'number_of_positions' => 1, 'status' => 'open',
    ];
    $this->post(route('admin.vacancies.store'), $payload)->assertSessionHasErrors('institution_id');
    $this->put(route('admin.announcements.update', $announcement), array_replace(announcementFormData($announcement), ['institution_ids' => []]))
        ->assertSessionHasErrors('institution_ids');
    $this->delete(route('admin.announcements.destroy', $announcement))->assertSessionHasErrors('announcement');
});

function withLegacyAnnouncementDatabase(Closure $test): void
{
    $default = DB::getDefaultConnection();
    config(['database.connections.announcement_migration' => ['driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true]]);
    DB::setDefaultConnection('announcement_migration');
    try {
        Schema::create('institutions', function ($table): void {
            $table->uuid('id')->primary();
        });
        $original = require database_path('migrations/2026_05_18_223246_create_vacancy_announcements_table.php');
        $original->up();
        Schema::table('vacancy_announcements', function ($table): void {
            $table->string('status')->default('draft');
        });
        Schema::create('vacancies', function ($table): void {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->string('code');
            $table->uuid('institution_id')->nullable();
            $table->date('opening_date');
            $table->date('closing_date');
            $table->string('status');
            $table->timestamp('published_at')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('opening_date', 'idx_vacancies_opening_date');
            $table->index('closing_date', 'idx_vacancies_closing_date');
        });
        Schema::create('applications', function ($table): void {
            $table->id();
            $table->foreignUuid('vacancy_id')->constrained()->restrictOnDelete();
        });
        DB::table('institutions')->insert(['id' => 'institution-one']);
        foreach (['one', 'two'] as $id) {
            DB::table('vacancies')->insert([
                'id' => $id, 'title' => '{"en":"Legacy Position"}', 'code' => $id,
                'opening_date' => '2026-09-01', 'closing_date' => $id === 'one' ? '2026-09-20' : '2026-09-25',
                'status' => 'open', 'institution_id' => 'institution-one', 'published_at' => '2026-08-30',
                'created_at' => '2026-08-30', 'updated_at' => '2026-08-30',
                'deleted_at' => $id === 'two' ? '2026-09-02' : null,
            ]);
        }
        DB::table('applications')->insert(['vacancy_id' => 'one']);
        $test();
    } finally {
        DB::setDefaultConnection($default);
        DB::purge('announcement_migration');
    }
}

test('legacy migration preserves periods applications institutions and deleted vacancies', function (): void {
    withLegacyAnnouncementDatabase(function (): void {
        $expand = require database_path('migrations/2026_09_30_144954_move_vacancy_dates_to_recruitment_announcements.php');
        $contract = require database_path('migrations/2026_09_30_144954_remove_dates_from_vacancies.php');
        $expand->up();
        $contract->up();
        foreach (DB::table('vacancies')->get() as $position) {
            $announcement = DB::table('recruitment_announcements')->find($position->announcement_id);
            expect($announcement->opening_date)->toBe('2026-09-01')
                ->and($announcement->closing_date)->toBe($position->id === 'one' ? '2026-09-20' : '2026-09-25')
                ->and(DB::table('announcement_institution')->where('announcement_id', $announcement->id)->value('institution_id'))->toBe('institution-one');
        }
        expect(DB::table('applications')->value('vacancy_id'))->toBe('one')
            ->and(DB::table('vacancies')->where('id', 'two')->value('deleted_at'))->not->toBeNull();
        $contract->down();
        $expand->down();
        expect(DB::table('vacancies')->where('id', 'one')->value('closing_date'))->toBe('2026-09-20')
            ->and(DB::table('applications')->count())->toBe(1);
    });
});

test('column removal refuses to discard unmatched dates', function (): void {
    withLegacyAnnouncementDatabase(function (): void {
        $expand = require database_path('migrations/2026_09_30_144954_move_vacancy_dates_to_recruitment_announcements.php');
        $contract = require database_path('migrations/2026_09_30_144954_remove_dates_from_vacancies.php');
        $expand->up();
        DB::table('vacancies')->where('id', 'one')->update(['closing_date' => '2030-01-01']);
        expect(fn () => $contract->up())->toThrow(RuntimeException::class)
            ->and(Schema::hasColumn('vacancies', 'closing_date'))->toBeTrue();
    });
});

test('vacancy reports export the parent announcement period', function (): void {
    $vacancy = Vacancy::factory()->create();
    app(SubmitApplicationAction::class)->handle(Applicant::factory()->create(), $vacancy, announcementApplicationData());
    $export = new VacancyWiseApplicantReportExport(['vacancy_id' => $vacancy->id]);
    $row = $export->collection()->first();
    expect(array_slice($row, -3))->toBe([
        $vacancy->announcement->code,
        $vacancy->announcement->opening_date->toDateString(),
        $vacancy->announcement->closing_date->toDateString(),
    ]);
    $this->actingAs(User::factory()->admin()->create())->get(route('admin.reports.index'))
        ->assertOk()->assertSee(et_date($vacancy->announcement->opening_date))
        ->assertSee(et_date($vacancy->announcement->closing_date));
});

test('announcement and vacancy forms preserve English and Amharic date presentation', function (string $locale): void {
    $vacancy = Vacancy::factory()->create();
    $this->actingAs(User::factory()->admin()->create(['preferred_locale' => $locale]));
    $this->withSession(['locale' => $locale]);
    app()->setLocale($locale);
    if ($locale === 'am') {
        expect(__('vacancies.announcement'))->toBe('የቅጥር ማስታወቂያ');
    }
    foreach ([route('admin.announcements.create'), route('admin.announcements.edit', $vacancy->announcement)] as $url) {
        $this->get($url)->assertOk()
            ->assertSee($locale === 'am' ? "ethiopianDatepicker('opening_date'," : 'name="opening_date"', false)
            ->assertSee($locale === 'am' ? "ethiopianDatepicker('closing_date'," : 'name="closing_date"', false)
            ->assertSee('name="institution_ids[]"', false);
    }
    $this->get(route('admin.announcements.show', $vacancy->announcement))->assertOk()
        ->assertSee(route('admin.vacancies.create', ['announcement_id' => $vacancy->announcement_id]), false);
    $this->get(route('admin.vacancies.edit', $vacancy))->assertOk()
        ->assertDontSee('name="opening_date"', false)->assertDontSee('name="closing_date"', false)
        ->assertSee(et_date($vacancy->announcement->opening_date, 'M d, Y'));
})->with(['en', 'am']);
