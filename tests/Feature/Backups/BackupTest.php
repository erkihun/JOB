<?php

declare(strict_types=1);

use App\Actions\Backups\RestoreBackupAction;
use App\Jobs\RestoreBackupJob;
use App\Jobs\RunBackupJob;
use App\Models\Applicant;
use App\Models\AuditLog;
use App\Models\BackupRecord;
use App\Models\Setting;
use App\Models\User;
use App\Services\BackupService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('backup_local');
    Storage::fake('local');
    Storage::fake('public');
    config(['backup.disk' => 'backup_local', 'backup.encryption_key' => null]);
});

function backupKey(): string
{
    return 'base64:'.base64_encode(str_repeat('k', 32));
}

/** Sample private and public files, plus files that must never be backed up. */
function seedBackupFiles(): void
{
    Storage::disk('local')->put('applicant-documents/1/degree.pdf', 'DEGREE-V1');
    Storage::disk('local')->put('applicants/photos/1/me.jpg', 'PHOTO');
    Storage::disk('local')->put('reports/applicants-report.xlsx', 'REPORT');
    Storage::disk('local')->put('temp/reg-docs/upload.pdf', 'TEMP');
    Storage::disk('local')->put('applications/9/cache/x.bin', 'CACHE');
    Storage::disk('public')->put('hero-sliders/hero.jpg', 'HERO');
    Storage::disk('public')->put('org/logo.png', 'LOGO');
}

function documentsBackup(): BackupRecord
{
    seedBackupFiles();

    return app(BackupService::class)->queue('documents')->refresh();
}

/** Entries of the stored (unencrypted) archive. */
function archiveEntries(BackupRecord $record): array
{
    $zip = new ZipArchive;
    $zip->open(Storage::disk('backup_local')->path($record->path));
    $names = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $names[] = $zip->getNameIndex($i);
    }
    $zip->close();

    return $names;
}

function settingsPayload(array $overrides = []): array
{
    return $overrides + [
        'type' => 'documents', 'enabled' => '1', 'frequency' => 'daily', 'time' => '02:00',
        'retention_days' => 30, 'max_copies' => 10, 'destination' => 'local', 'compress' => '1',
        'includes' => ['private_documents', 'profile_photos'],
    ];
}

// ── Authorization ───────────────────────────────────────────────────────────

test('users without backup permissions cannot view or manage backups', function (): void {
    $officer = User::factory()->screeningOfficer()->create();

    $this->actingAs($officer)->get(route('admin.backups.index'))->assertForbidden();
    $this->put(route('admin.backups.update'), settingsPayload())->assertForbidden();
    $this->post(route('admin.backups.run', 'database'))->assertForbidden();

    expect(BackupRecord::count())->toBe(0)
        ->and(Setting::where('key', 'like', 'backup.%')->count())->toBe(0);
});

test('guests and applicants can never reach backup files', function (): void {
    Queue::fake();
    $record = BackupRecord::create(['type' => 'documents', 'destination' => 'local', 'disk' => 'backup_local', 'status' => 'success', 'path' => 'backups/x.zip', 'checksum' => str_repeat('a', 64)]);
    Storage::disk('backup_local')->put('backups/x.zip', 'secret');

    $this->get(route('admin.backups.download', $record))->assertRedirect(route('login'));

    $applicant = Applicant::factory()->create();
    $this->actingAs($applicant->user)->get(route('admin.backups.download', $record))->assertRedirect();
    $this->actingAs($applicant->user)->get(route('admin.backups.index'))->assertRedirect();

    expect(config('filesystems.disks.backup_local.root'))->not->toContain('public');
});

test('admins see the backup dashboard with health, usage and manual buttons', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('admin.backups.index'))
        ->assertOk()
        ->assertSee(__('backups.database'))
        ->assertSee(__('backups.documents'))
        ->assertSee(__('backups.storage_usage'))
        ->assertSee(__('backups.backup_now'))
        ->assertSee(__('backups.history'))
        ->assertDontSee(__('backups.restore_title')); // admin has no restore permission
});

// ── Running backups ─────────────────────────────────────────────────────────

test('an authorized user can queue a manual backup, once per type', function (): void {
    Queue::fake();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.backups.run', 'documents'))
        ->assertRedirect(route('admin.backups.index'))->assertSessionHas('success', __('backups.queued'));
    $this->post(route('admin.backups.run', 'documents'))->assertSessionHas('success', __('backups.already_running'));

    $record = BackupRecord::sole();
    expect($record->status)->toBe('queued')
        ->and($record->initiated_by)->toBe($admin->id)
        ->and(AuditLog::where('action', 'backup_queued')->where('record_id', $record->id)->exists())->toBeTrue();
    Queue::assertPushed(RunBackupJob::class, 1);
});

test('a document backup records history with size and checksum and skips temp, cache and log files', function (): void {
    $record = documentsBackup();

    expect($record->status)->toBe('success')
        ->and($record->size)->toBeGreaterThan(0)
        ->and($record->checksum)->toBe(hash('sha256', Storage::disk('backup_local')->get($record->path)))
        ->and($record->started_at)->not->toBeNull()
        ->and($record->completed_at)->not->toBeNull()
        ->and($record->options['files'])->toBe(5);

    expect(archiveEntries($record))->toContain(
        'manifest.json',
        'files/local/applicant-documents/1/degree.pdf',
        'files/local/applicants/photos/1/me.jpg',
        'files/local/reports/applicants-report.xlsx',
        'files/public/hero-sliders/hero.jpg',
        'files/public/org/logo.png',
    )->not->toContain('files/local/temp/reg-docs/upload.pdf', 'files/local/applications/9/cache/x.bin');
});

test('a failed backup is recorded with its reason', function (): void {
    // The test database is in-memory SQLite, which cannot be dumped.
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.backups.run', 'database'))
        ->assertRedirect(route('admin.backups.index'))->assertSessionHas('error');

    $record = BackupRecord::sole();
    expect($record->status)->toBe('failed')
        ->and($record->failure_message)->toBe('Cannot back up an in-memory database.')
        ->and($record->completed_at)->not->toBeNull();

    $this->get(route('admin.backups.index'))->assertOk()
        ->assertSee(__('backups.state.failed'))
        ->assertSee('Cannot back up an in-memory database.');
});

test('encrypted backups are unreadable without the key and reject tampering', function (): void {
    config(['backup.encryption_key' => backupKey()]);
    Setting::set('backup.documents.encrypt', true, 'boolean', 'backup');
    $record = documentsBackup();
    $stored = Storage::disk('backup_local')->path($record->path);
    $service = app(BackupService::class);

    expect($record->path)->toEndWith('.zip.enc')
        ->and(file_get_contents($stored))->toStartWith('JOBSBAK1')->not->toContain('DEGREE-V1');

    $plain = $stored.'.zip';
    $service->crypt($stored, $plain, true);
    $zip = new ZipArchive;
    expect($zip->open($plain))->toBeTrue()
        ->and($zip->getFromName('files/local/applicant-documents/1/degree.pdf'))->toBe('DEGREE-V1');
    $zip->close();

    $bytes = file_get_contents($stored);
    $bytes[40] = $bytes[40] === 'A' ? 'B' : 'A';
    file_put_contents($stored, $bytes);
    expect(fn () => $service->crypt($stored, $plain, true))->toThrow(RuntimeException::class);
});

test('retention keeps at most the configured number of copies', function (): void {
    Setting::set('backup.documents.max_copies', 1, 'integer', 'backup');
    $first = documentsBackup();
    $firstPath = $first->path;
    $second = app(BackupService::class)->queue('documents')->refresh();

    expect($second->status)->toBe('success')
        ->and($first->refresh()->status)->toBe('pruned')
        ->and($first->path)->toBeNull()
        ->and(Storage::disk('backup_local')->exists($firstPath))->toBeFalse()
        ->and(Storage::disk('backup_local')->exists($second->path))->toBeTrue();
});

// ── Settings ────────────────────────────────────────────────────────────────

test('secrets are never stored in the settings table', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->put(route('admin.backups.update'), settingsPayload([
        'encryption_key' => 'base64:SHOULD-NOT-BE-STORED',
        'aws_secret_access_key' => 'SHOULD-NOT-BE-STORED',
        'backup_disk' => 'public',
    ]))->assertSessionHasNoErrors();

    $stored = Setting::where('key', 'like', 'backup.%')->get();
    expect($stored)->not->toBeEmpty()
        ->and($stored->pluck('value')->implode('|'))->not->toContain('SHOULD-NOT-BE-STORED')
        ->and($stored->pluck('key')->filter(fn ($key) => str_contains($key, 'key') || str_contains($key, 'secret') || str_contains($key, 'disk'))->all())->toBe([]);
});

test('settings are validated', function (array $overrides, string $field): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->put(route('admin.backups.update'), settingsPayload($overrides))->assertSessionHasErrors($field);
})->with([
    'unknown frequency' => [['frequency' => 'yearly'], 'frequency'],
    'invalid time' => [['time' => '25:00'], 'time'],
    'zero retention' => [['retention_days' => 0], 'retention_days'],
    'too many copies' => [['max_copies' => 5000], 'max_copies'],
    'unknown destination' => [['destination' => 'ftp'], 'destination'],
    's3 not configured' => [['destination' => 's3'], 'destination'],
    'nothing to include' => [['includes' => []], 'includes'],
    'unknown group' => [['includes' => ['passwords']], 'includes.0'],
    'encryption without a key' => [['encrypt' => '1'], 'encrypt'],
]);

test('valid settings are saved and audited', function (): void {
    $admin = User::factory()->admin()->create();
    config(['backup.encryption_key' => backupKey()]);

    $this->actingAs($admin)->put(route('admin.backups.update'), settingsPayload(['encrypt' => '1', 'frequency' => 'weekly', 'time' => '03:30']))
        ->assertSessionHasNoErrors();

    $backups = app(BackupService::class);
    expect($backups->preference('documents', 'frequency'))->toBe('weekly')
        ->and($backups->preference('documents', 'time'))->toBe('03:30')
        ->and($backups->preference('documents', 'encrypt'))->toBeTrue()
        ->and($backups->preference('documents', 'includes'))->toBe(['private_documents', 'profile_photos'])
        ->and(AuditLog::where('action', 'backup_settings_updated')->exists())->toBeTrue();
});

// ── Scheduler ───────────────────────────────────────────────────────────────

test('the scheduler queues each due slot exactly once', function (): void {
    Queue::fake();
    $this->travelTo('2026-10-01 12:00:00');
    $this->actingAs(User::factory()->admin()->create())->put(route('admin.backups.update'), settingsPayload())->assertSessionHasNoErrors();
    $backups = app(BackupService::class);

    // Enabling does not fire for the slot that already passed today.
    $this->artisan('backups:dispatch-due')->assertSuccessful();
    expect(BackupRecord::count())->toBe(0)
        ->and($backups->nextRunAt('documents')?->format('Y-m-d H:i'))->toBe('2026-10-02 02:00');

    $this->travelTo('2026-10-02 01:59:00');
    $this->artisan('backups:dispatch-due');
    expect(BackupRecord::count())->toBe(0);

    $this->travelTo('2026-10-02 02:00:00');
    $this->artisan('backups:dispatch-due');
    $this->travelTo('2026-10-02 02:05:00');
    $this->artisan('backups:dispatch-due');
    expect(BackupRecord::count())->toBe(1);
    BackupRecord::query()->update(['status' => 'success']);

    // A missed exact minute is caught up on the next run.
    $this->travelTo('2026-10-03 02:07:00');
    $this->artisan('backups:dispatch-due');
    expect(BackupRecord::count())->toBe(2);
    Queue::assertPushed(RunBackupJob::class, 2);
});

test('a backup stuck in the queue is failed so it no longer blocks new runs', function (): void {
    Queue::fake();
    $record = BackupRecord::create(['type' => 'database', 'destination' => 'local', 'disk' => 'backup_local', 'status' => 'running']);
    BackupRecord::whereKey($record->id)->update(['updated_at' => now()->subHours(4)]);

    $this->artisan('backups:dispatch-due')->assertSuccessful();

    expect($record->refresh()->status)->toBe('failed');
});

// ── Download / delete / restore permissions ────────────────────────────────

test('downloading a backup requires the download permission and is audited', function (): void {
    $record = documentsBackup();

    $this->actingAs(User::factory()->screeningOfficer()->create())->get(route('admin.backups.download', $record))->assertForbidden();
    $this->actingAs(User::factory()->reportViewer()->create())->get(route('admin.backups.download', $record))->assertForbidden();

    $response = $this->actingAs(User::factory()->admin()->create())->get(route('admin.backups.download', $record));
    $response->assertOk()->assertHeader('Content-Type', 'application/octet-stream');
    expect($response->streamedContent())->toBe(Storage::disk('backup_local')->get($record->path))
        ->and(AuditLog::where('action', 'backup_downloaded')->where('record_id', $record->id)->exists())->toBeTrue();
});

test('deleting a backup needs the delete permission and the current password', function (): void {
    $record = documentsBackup();
    $path = $record->path;

    $this->actingAs(User::factory()->admin()->create())->delete(route('admin.backups.destroy', $record), ['password' => 'password'])
        ->assertForbidden();

    $super = User::factory()->superAdmin()->create();
    $this->actingAs($super)->delete(route('admin.backups.destroy', $record), ['password' => 'wrong'])->assertSessionHasErrors('password');
    expect(Storage::disk('backup_local')->exists($path))->toBeTrue();

    $this->delete(route('admin.backups.destroy', $record), ['password' => 'password'])->assertSessionHasNoErrors();
    expect(Storage::disk('backup_local')->exists($path))->toBeFalse()
        ->and($record->refresh()->status)->toBe('deleted')
        ->and(AuditLog::where('action', 'backup_deleted')->where('record_id', $record->id)->exists())->toBeTrue();
});

test('restore requires the restore permission, confirmation and the current password', function (): void {
    $record = documentsBackup();
    Queue::fake();

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.backups.restore', $record), ['confirmation' => 'RESTORE', 'password' => 'password'])
        ->assertForbidden();

    $super = User::factory()->superAdmin()->create();
    $this->actingAs($super)->post(route('admin.backups.restore', $record), ['password' => 'password'])->assertSessionHasErrors('confirmation');
    $this->post(route('admin.backups.restore', $record), ['confirmation' => 'restore please', 'password' => 'password'])->assertSessionHasErrors('confirmation');
    $this->post(route('admin.backups.restore', $record), ['confirmation' => 'RESTORE', 'password' => 'wrong'])->assertSessionHasErrors('password');
    Queue::assertNothingPushed();

    $this->post(route('admin.backups.restore', $record), ['confirmation' => 'RESTORE', 'password' => 'password'])
        ->assertSessionHasNoErrors()->assertSessionHas('success', __('backups.restore_queued'));

    Queue::assertPushed(RestoreBackupJob::class, fn ($job) => $job->backupId === $record->id && $job->userId === $super->id);
    expect(AuditLog::where('action', 'backup_restore_requested')->where('record_id', $record->id)->exists())->toBeTrue();
});

// ── Restore ─────────────────────────────────────────────────────────────────

test('restoring documents validates the checksum, takes a pre-restore backup and puts files back', function (): void {
    config(['backup.encryption_key' => backupKey()]);
    Setting::set('backup.documents.encrypt', true, 'boolean', 'backup');
    $record = documentsBackup();
    Storage::disk('local')->put('applicant-documents/1/degree.pdf', 'DEGREE-OVERWRITTEN');
    $super = User::factory()->superAdmin()->create();

    app(RestoreBackupAction::class)->handle($record, $super->id);

    expect(Storage::disk('local')->get('applicant-documents/1/degree.pdf'))->toBe('DEGREE-V1')
        ->and(app()->isDownForMaintenance())->toBeFalse();

    $rollback = BackupRecord::where('options->pre_restore', true)->sole();
    expect($rollback->status)->toBe('success')
        ->and($record->refresh()->options['last_restore']['status'])->toBe('success')
        ->and($record->options['last_restore']['rollback_backup_id'])->toBe($rollback->id);

    $audit = AuditLog::where('action', 'backup_restored')->sole();
    expect($audit->user_id)->toBe($super->id)
        ->and($audit->new_values['rollback_backup_id'])->toBe($rollback->id);
});

test('a backup whose file no longer matches its checksum is never restored', function (): void {
    $record = documentsBackup();
    Storage::disk('backup_local')->put($record->path, 'tampered');
    Storage::disk('local')->put('applicant-documents/1/degree.pdf', 'CURRENT');

    expect(fn () => app(RestoreBackupAction::class)->handle($record))->toThrow(RuntimeException::class, __('backups.checksum_mismatch'));

    expect(Storage::disk('local')->get('applicant-documents/1/degree.pdf'))->toBe('CURRENT')
        ->and(BackupRecord::where('options->pre_restore', true)->exists())->toBeFalse()
        ->and(AuditLog::where('action', 'backup_restore_failed')->where('record_id', $record->id)->exists())->toBeTrue()
        ->and(app()->isDownForMaintenance())->toBeFalse();
});

test('backup wording exists in English and Amharic', function (): void {
    $en = require lang_path('en/backups.php');
    $am = require lang_path('am/backups.php');

    expect(array_keys(Arr::dot($am)))->toEqualCanonicalizing(array_keys(Arr::dot($en)));
    app()->setLocale('am');
    expect(__('backups.state.failed'))->toMatch('/\p{Ethiopic}/u');
});

test('a database backup restores the database and keeps the backup history', function (): void {
    // In-memory SQLite cannot be dumped, so this round-trip uses a temporary file database.
    $file = tempnam(sys_get_temp_dir(), 'backup-roundtrip');
    $default = DB::getDefaultConnection();
    config(['database.connections.roundtrip' => ['driver' => 'sqlite', 'database' => $file, 'prefix' => '', 'foreign_key_constraints' => true]]);
    DB::setDefaultConnection('roundtrip');

    try {
        Artisan::call('migrate', ['--database' => 'roundtrip', '--force' => true]);
        Setting::set('probe.value', 'before-backup');

        $record = app(BackupService::class)->queue('database')->refresh();
        expect($record->status)->toBe('success')
            ->and(archiveEntries($record))->toContain('database/database.sqlite');

        Setting::set('probe.value', 'changed-later');
        app(RestoreBackupAction::class)->handle($record);

        expect(Setting::get('probe.value'))->toBe('before-backup')
            // The backup itself and its pre-restore copy survive the restore.
            ->and(BackupRecord::whereKey($record->id)->exists())->toBeTrue()
            ->and(BackupRecord::where('options->pre_restore', true)->where('status', 'success')->exists())->toBeTrue()
            ->and(AuditLog::where('action', 'backup_restored')->exists())->toBeTrue();
    } finally {
        DB::setDefaultConnection($default);
        DB::purge('roundtrip');
        @unlink($file);
    }
});
