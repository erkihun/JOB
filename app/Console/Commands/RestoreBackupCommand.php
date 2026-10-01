<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Backups\RestoreBackupAction;
use App\Models\BackupRecord;
use Illuminate\Console\Command;
use Throwable;

/**
 * Operator restore from the server shell (see docs/DEPLOYMENT.md § Restore).
 * Shell access is the authorisation here; the same checksum validation,
 * pre-restore backup, maintenance mode and audit trail as the web restore apply.
 */
class RestoreBackupCommand extends Command
{
    protected $signature = 'backups:restore {backup : Backup ID (see backups:list)} {--force : Skip the interactive confirmation}';

    protected $description = 'Restore a database or document backup (creates a pre-restore backup first)';

    public function handle(RestoreBackupAction $restore): int
    {
        $record = BackupRecord::find($this->argument('backup'));
        if ($record === null) {
            $this->error('Backup not found.');

            return self::FAILURE;
        }

        $this->table(['ID', 'Type', 'Completed', 'Size', 'Checksum'], [[
            $record->id, $record->type, $record->completed_at?->toDateTimeString(), $record->size, $record->checksum,
        ]]);

        if (! $this->option('force') && $this->ask('This replaces live '.$record->type.' data. Type RESTORE to continue') !== 'RESTORE') {
            $this->warn('Restore cancelled.');

            return self::FAILURE;
        }

        try {
            $restored = $restore->handle($record);
        } catch (Throwable $e) {
            $this->error('Restore failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Restore complete. Rollback backup: '.($restored->options['last_restore']['rollback_backup_id'] ?? '—'));

        return self::SUCCESS;
    }
}
