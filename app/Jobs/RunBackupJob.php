<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\BackupRecord;
use App\Services\BackupService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\MaxAttemptsExceededException;
use Throwable;

class RunBackupJob implements ShouldQueue
{
    use Queueable;

    /** Backups are never retried automatically; a failure is recorded and shown. */
    public int $tries = 1;

    public int $timeout = 3600;

    public bool $failOnTimeout = true;

    public function __construct(public readonly string $backupId) {}

    public function handle(BackupService $backups): void
    {
        $record = BackupRecord::find($this->backupId);

        if ($record !== null) {
            $backups->run($record);
        }
    }

    /** Covers worker timeouts/kills, where run() never reaches its catch block. */
    public function failed(?Throwable $exception): void
    {
        // A redelivery while the first worker is still running (queue retry_after
        // shorter than the backup) must not mark the live backup as failed.
        if ($exception instanceof MaxAttemptsExceededException && BackupRecord::whereKey($this->backupId)
            ->where('status', 'running')->where('started_at', '>', now()->subSeconds($this->timeout))->exists()) {
            return;
        }

        BackupRecord::whereKey($this->backupId)->whereIn('status', ['queued', 'running'])->update([
            'status' => 'failed',
            'failure_message' => mb_substr($exception?->getMessage() ?: 'Backup job failed.', 0, 1000),
            'completed_at' => now(),
        ]);
    }
}
