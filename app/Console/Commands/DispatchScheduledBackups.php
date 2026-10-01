<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Runs every minute from the scheduler (routes/console.php) and queues the
 * database / document backups whose scheduled slot has come.
 *
 * - a slot is remembered once handled (`backup.{type}.last_slot`), so a slot is
 *   never queued twice, and one missed minute does not skip the whole day
 * - a cache lock serialises overlapping runs on several servers
 * - BackupService::queue() refuses to start a second backup of the same type
 *   while one is still queued or running
 */
class DispatchScheduledBackups extends Command
{
    protected $signature = 'backups:dispatch-due';

    protected $description = 'Queue database and document backups whose scheduled time has come';

    public function handle(BackupService $backups): int
    {
        $stale = $backups->failStale();
        if ($stale > 0) {
            $this->warn("Marked {$stale} stalled backup(s) as failed.");
        }

        foreach (BackupService::TYPES as $type) {
            if (! $backups->isDue($type)) {
                continue;
            }

            Cache::lock("backups:dispatch:{$type}", 120)->get(function () use ($backups, $type): void {
                if (! $backups->isDue($type)) {
                    return;
                }

                $slot = $backups->lastDueSlot($type);
                try {
                    $record = $backups->queue($type);
                    $this->info("Queued {$type} backup {$record->id} for slot {$slot->format('Y-m-d H:i')}.");
                } catch (Throwable $e) {
                    report($e);
                    $this->error("Could not queue {$type} backup: {$e->getMessage()}");
                } finally {
                    // Handled either way; a misconfiguration is reported, not retried every minute.
                    $backups->markSlotHandled($type, $slot);
                }
            });
        }

        return self::SUCCESS;
    }
}
