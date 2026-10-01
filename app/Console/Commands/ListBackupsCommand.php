<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\BackupRecord;
use Illuminate\Console\Command;

class ListBackupsCommand extends Command
{
    protected $signature = 'backups:list {--type= : database|documents} {--limit=20}';

    protected $description = 'List recent backups with their status and checksum';

    public function handle(): int
    {
        $rows = BackupRecord::query()
            ->when($this->option('type'), fn ($q, $type) => $q->where('type', $type))
            ->latest()->limit((int) $this->option('limit'))->get()
            ->map(fn (BackupRecord $r) => [
                $r->id, $r->type, $r->status, $r->destination, $r->completed_at?->toDateTimeString(),
                $r->size !== null ? number_format($r->size / 1048576, 2).' MB' : '—', substr((string) $r->checksum, 0, 12),
            ]);

        $this->table(['ID', 'Type', 'Status', 'Destination', 'Completed', 'Size', 'Checksum'], $rows);

        return self::SUCCESS;
    }
}
