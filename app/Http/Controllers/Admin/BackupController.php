<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateBackupSettingsRequest;
use App\Jobs\RestoreBackupJob;
use App\Models\AuditLog;
use App\Models\BackupRecord;
use App\Models\Setting;
use App\Services\BackupService;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use PragmaRX\Google2FA\Google2FA;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * System Settings → Backup. Every action is behind its own permission (route
 * middleware): backups.view / settings.manage / run / download / restore / delete.
 */
class BackupController extends Controller
{
    private const PREFERENCE_TYPES = [
        'enabled' => 'boolean', 'frequency' => 'string', 'time' => 'string',
        'retention_days' => 'integer', 'max_copies' => 'integer', 'destination' => 'string',
        'compress' => 'boolean', 'encrypt' => 'boolean',
    ];

    public function index(BackupService $backups): View
    {
        $health = [];
        foreach (BackupService::TYPES as $type) {
            $lastSuccess = BackupRecord::where('type', $type)->where('status', 'success')->latest('completed_at')->first();
            $last = BackupRecord::where('type', $type)->where(fn ($q) => $q->whereNull('options->pre_restore')->orWhere('options->pre_restore', false))
                ->latest()->first();
            $health[$type] = [
                'preferences' => $backups->preferences($type),
                'last' => $last,
                'last_success' => $lastSuccess,
                'next_run' => $backups->nextRunAt($type),
                // Healthy = last attempt did not fail and, if scheduled, a success within two periods.
                'state' => match (true) {
                    $last?->status === 'failed' => 'failed',
                    in_array($last?->status, ['queued', 'running'], true) => 'running',
                    $lastSuccess === null => 'never',
                    $backups->preference($type, 'enabled') && $lastSuccess->completed_at?->lt($this->staleThreshold($backups, $type)) => 'overdue',
                    default => 'healthy',
                },
            ];
        }

        return view('admin.settings.backups', [
            'health' => $health,
            'history' => BackupRecord::with('initiator')->latest()->paginate(15),
            'usage' => $backups->storageUsage(),
            's3Available' => $backups->s3Available(),
            'keyConfigured' => $backups->encryptionKeyConfigured(),
            'localDisk' => (string) config('backup.disk'),
        ]);
    }

    public function update(UpdateBackupSettingsRequest $request, BackupService $backups): RedirectResponse
    {
        $data = $request->validated();
        $type = $data['type'];
        $before = $backups->preferences($type);

        foreach (self::PREFERENCE_TYPES as $key => $kind) {
            Setting::set("backup.{$type}.{$key}", $data[$key], $kind, 'backup');
        }
        if ($type === 'database') {
            Setting::set('backup.database.include_database', (bool) ($data['include_database'] ?? false), 'boolean', 'backup');
        } else {
            Setting::set('backup.documents.includes', array_values(array_intersect(BackupService::DOCUMENT_GROUPS, $data['includes'] ?? [])), 'json', 'backup');
        }

        // A newly enabled or re-timed schedule starts with the next slot, not one that already passed.
        $after = $backups->preferences($type);
        if ($after['enabled'] && (! $before['enabled'] || $before['frequency'] !== $after['frequency'] || $before['time'] !== $after['time'])) {
            $backups->markSlotHandled($type);
        }

        AuditLog::record('backup_settings_updated', 'backups', null, ['type' => $type] + $before, ['type' => $type] + $after);

        return redirect()->route('admin.backups.index')->with('success', __('backups.settings_saved'));
    }

    public function run(Request $request, BackupService $backups, string $type): RedirectResponse
    {
        abort_unless(in_array($type, BackupService::TYPES, true), 404);

        try {
            $record = $backups->queue($type, (string) $request->user()->id)->refresh();
        } catch (Throwable $e) {
            // With a synchronous queue the job runs inline; its failure is already recorded.
            report($e);

            return redirect()->route('admin.backups.index')->with('error', __('backups.failed_with', ['message' => $e->getMessage()]));
        }

        AuditLog::record('backup_queued', 'backups', $record->id, null, ['type' => $type, 'manual' => true]);

        return redirect()->route('admin.backups.index')->with(
            $record->status === 'failed' ? 'error' : 'success',
            $record->wasRecentlyCreated ? __('backups.queued') : __('backups.already_running'),
        );
    }

    public function download(BackupRecord $backup): StreamedResponse
    {
        abort_unless($backup->status === 'success' && $backup->path, 404);
        $stream = Storage::disk($backup->disk)->readStream($backup->path);
        abort_unless(is_resource($stream), 404);

        AuditLog::record('backup_downloaded', 'backups', $backup->id, null, ['type' => $backup->type]);

        return response()->streamDownload(function () use ($stream): void {
            $out = fopen('php://output', 'wb');
            stream_copy_to_stream($stream, $out);
            fclose($stream);
        }, basename($backup->path), [
            'Content-Type' => 'application/octet-stream',
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function destroy(Request $request, BackupRecord $backup, BackupService $backups): RedirectResponse
    {
        $this->confirmIdentity($request);

        if (in_array($backup->status, ['queued', 'running'], true)) {
            return redirect()->route('admin.backups.index')->with('error', __('backups.active_cannot_delete'));
        }

        $backups->deleteArtifact($backup);
        AuditLog::record('backup_deleted', 'backups', $backup->id, $backup->only(['type', 'path', 'checksum', 'completed_at']));
        $backup->update(['status' => 'deleted', 'path' => null]);

        return redirect()->route('admin.backups.index')->with('success', __('backups.deleted'));
    }

    public function restore(Request $request, BackupRecord $backup): RedirectResponse
    {
        $request->validate(['confirmation' => ['required', 'string', 'in:RESTORE']], [
            'confirmation.in' => __('backups.restore_type_word'),
        ]);
        $this->confirmIdentity($request);

        if ($backup->status !== 'success' || ! $backup->path || ! $backup->checksum) {
            throw ValidationException::withMessages(['confirmation' => __('backups.restore_incomplete')]);
        }

        AuditLog::record('backup_restore_requested', 'backups', $backup->id, null, ['type' => $backup->type]);
        RestoreBackupJob::dispatch($backup->id, (string) $request->user()->id)->onQueue((string) config('backup.queue', 'default'));

        return redirect()->route('admin.backups.index')->with('success', __('backups.restore_queued'));
    }

    /**
     * Recent re-authentication for destructive actions: current password, plus a
     * TOTP code when the user has two-factor authentication enabled.
     */
    private function confirmIdentity(Request $request): void
    {
        $request->validate([
            'password' => ['required', 'string'],
            'one_time_password' => ['nullable', 'digits:6'],
        ]);
        $user = $request->user();

        if (! Hash::check((string) $request->input('password'), $user->password)) {
            AuditLog::record('backup_reauth_failed', 'backups', null, null, ['route' => $request->route()?->getName()]);

            throw ValidationException::withMessages(['password' => __('backups.password_incorrect')]);
        }

        if ($user->hasTwoFactorEnabled()
            && ! app(Google2FA::class)->verifyKey((string) $user->google2fa_secret, (string) $request->input('one_time_password'))) {
            throw ValidationException::withMessages(['one_time_password' => __('backups.otp_incorrect')]);
        }
    }

    private function staleThreshold(BackupService $backups, string $type): CarbonInterface
    {
        return match ((string) $backups->preference($type, 'frequency')) {
            'hourly' => now()->subHours(3),
            'weekly' => now()->subWeeks(2),
            'monthly' => now()->subMonths(2),
            default => now()->subDays(2),
        };
    }
}
