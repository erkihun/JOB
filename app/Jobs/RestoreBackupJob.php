<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Backups\RestoreBackupAction;
use App\Models\BackupRecord;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Runs an authorised restore off the web request (the web tier is put into
 * maintenance mode while it runs). Authorisation, confirmation and re-auth
 * happen in BackupController before this is dispatched.
 */
class RestoreBackupJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 3600;

    public bool $failOnTimeout = true;

    public function __construct(public readonly string $backupId, public readonly ?string $userId) {}

    public function handle(RestoreBackupAction $restore): void
    {
        $restore->handle(BackupRecord::findOrFail($this->backupId), $this->userId);
    }
}
