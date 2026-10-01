<?php

declare(strict_types=1);

namespace App\Actions\Backups;

use App\Models\AuditLog;
use App\Models\BackupRecord;
use App\Services\BackupService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;
use ZipArchive;

/**
 * Restores one backup. Order of operations:
 *   1. only a complete backup; download it and verify its SHA-256 checksum
 *   2. decrypt (authenticated) and check the manifest type
 *   3. take a pre-restore backup of the current state (rollback point)
 *   4. maintenance mode on → restore → maintenance mode off
 *   5. re-insert backup history that a database restore would otherwise lose, audit
 *
 * Callers are responsible for authorisation (BackupController / backups:restore).
 * Document restores overwrite archived files; files created after the backup are kept.
 */
class RestoreBackupAction
{
    public function __construct(private readonly BackupService $backups) {}

    public function handle(BackupRecord $record, ?string $userId = null): BackupRecord
    {
        if ($record->status !== 'success' || ! $record->path || ! $record->checksum) {
            throw new RuntimeException(__('backups.restore_incomplete'));
        }

        $work = $this->backups->workspace();
        $startedDown = false;
        $rollback = null;

        try {
            $artifact = $work.'/artifact';
            $this->download($record, $artifact);

            if (! hash_equals($record->checksum, (string) hash_file('sha256', $artifact))) {
                throw new RuntimeException(__('backups.checksum_mismatch'));
            }

            $archive = $artifact;
            if ($record->options['encrypt'] ?? false) {
                $archive = $work.'/archive.zip';
                $this->backups->crypt($artifact, $archive, true);
            }

            $zip = new ZipArchive;
            if ($zip->open($archive) !== true) {
                throw new RuntimeException('Backup archive is invalid.');
            }

            try {
                $manifest = json_decode((string) $zip->getFromName('manifest.json'), true, flags: JSON_THROW_ON_ERROR);
                if (($manifest['type'] ?? null) !== $record->type) {
                    throw new RuntimeException('Backup type does not match.');
                }

                // Rollback point before any live data changes. If it cannot be made, nothing is restored.
                $rollback = $this->preRestoreBackup($record, $userId);

                if (! app()->isDownForMaintenance()) {
                    Artisan::call('down', ['--retry' => 60]);
                    $startedDown = true;
                }

                $history = BackupRecord::query()->whereKey([$record->id, $rollback->id])->get()
                    ->map(fn (BackupRecord $row) => $row->getAttributes())->all();

                if ($record->type === 'database') {
                    $this->restoreDatabase($zip, $work);
                    // The restored database predates these rows; put them back.
                    foreach ($history as $attributes) {
                        DB::table('backup_records')->updateOrInsert(['id' => $attributes['id']], $attributes);
                    }
                } else {
                    $this->restoreDocuments($zip);
                }
            } finally {
                $zip->close();
            }

            $record->refresh();
            $record->update(['options' => [...(array) $record->options, 'last_restore' => [
                'status' => 'success', 'at' => now()->toIso8601String(), 'by' => $userId, 'rollback_backup_id' => $rollback->id,
            ]]]);
            $this->audit('backup_restored', $record, $userId, ['rollback_backup_id' => $rollback->id, 'type' => $record->type]);

            return $record;
        } catch (Throwable $e) {
            BackupRecord::whereKey($record->id)->update(['options' => json_encode([...(array) $record->options, 'last_restore' => [
                'status' => 'failed', 'at' => now()->toIso8601String(), 'by' => $userId, 'message' => mb_substr($e->getMessage(), 0, 300),
            ]])]);
            $this->audit('backup_restore_failed', $record, $userId, [
                'reason' => mb_substr($e->getMessage(), 0, 300), 'rollback_backup_id' => $rollback?->id,
            ]);

            throw $e;
        } finally {
            if ($startedDown) {
                Artisan::call('up');
            }
            $this->backups->cleanWorkspace($work);
        }
    }

    private function download(BackupRecord $record, string $target): void
    {
        $input = Storage::disk($record->disk)->readStream($record->path);
        $output = fopen($target, 'wb');
        if (! is_resource($input) || $output === false) {
            throw new RuntimeException(__('backups.artifact_missing'));
        }
        stream_copy_to_stream($input, $output);
        fclose($input);
        fclose($output);
    }

    private function preRestoreBackup(BackupRecord $record, ?string $userId): BackupRecord
    {
        $encrypt = $this->backups->encryptionKeyConfigured();
        $rollback = BackupRecord::create([
            'type' => $record->type,
            'destination' => 'local',
            'disk' => $this->backups->disk('local'),
            'status' => 'queued',
            'initiated_by' => $userId,
            'options' => [
                'encrypt' => $encrypt, 'compress' => true, 'include_database' => true,
                'includes' => BackupService::DOCUMENT_GROUPS, 'pre_restore' => true, 'restoring' => $record->id,
            ],
        ]);
        $this->backups->run($rollback);

        return $rollback->refresh();
    }

    private function restoreDatabase(ZipArchive $zip, string $work): void
    {
        $connection = config('database.default');
        $db = config("database.connections.{$connection}");

        if ($db['driver'] === 'sqlite') {
            $snapshot = $zip->getFromName('database/database.sqlite');
            if ($snapshot === false || $db['database'] === ':memory:') {
                throw new RuntimeException('SQLite snapshot is missing.');
            }
            DB::disconnect($connection);
            $target = $db['database'];
            if (file_put_contents($target.'.restore', $snapshot) === false || ! rename($target.'.restore', $target)) {
                throw new RuntimeException('SQLite restore failed.');
            }
            DB::reconnect($connection);

            return;
        }

        if ($zip->locateName('database/database.sql') === false) {
            throw new RuntimeException('SQL dump is missing.');
        }
        $file = $work.'/restore.sql';
        $in = $zip->getStream('database/database.sql');
        $out = fopen($file, 'wb');
        if ($in === false || $out === false) {
            throw new RuntimeException('Cannot extract SQL dump.');
        }
        stream_copy_to_stream($in, $out);
        fclose($in);
        fclose($out);

        if ($db['driver'] === 'pgsql') {
            $command = [config('backup.psql', 'psql'), '--set=ON_ERROR_STOP=1', '--single-transaction', '--host='.$db['host'],
                '--port='.(string) $db['port'], '--username='.$db['username'], '--dbname='.$db['database'], '--file='.$file];
            $env = ['PGPASSWORD' => (string) $db['password']];
            $process = new Process($command, base_path(), $env, null, 3600);
        } elseif (in_array($db['driver'], ['mysql', 'mariadb'], true)) {
            $command = [config('backup.mysql', 'mysql'), '--host='.$db['host'], '--port='.(string) $db['port'], '--user='.$db['username'], $db['database']];
            $process = new Process($command, base_path(), ['MYSQL_PWD' => (string) $db['password']], null, 3600);
            $process->setInput(fopen($file, 'rb'));
        } else {
            throw new RuntimeException('Unsupported database driver.');
        }

        DB::disconnect($connection);
        $process->mustRun();
        DB::reconnect($connection);
    }

    private function restoreDocuments(ZipArchive $zip): void
    {
        $allowed = [];
        foreach (BackupService::DOCUMENT_FOLDERS as $disks) {
            foreach ($disks as $disk => $folders) {
                foreach ($folders as $folder) {
                    $allowed[] = $disk.'/'.$folder.'/';
                }
            }
        }

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);
            if (! is_string($name) || ! str_starts_with($name, 'files/') || str_ends_with($name, '/')) {
                continue;
            }
            // Only known disks/folders, no traversal: an archive can never write elsewhere.
            if (! preg_match('~^files/(local|public)/([A-Za-z0-9_./-]+)$~', $name, $parts)
                || str_contains($parts[2], '..')
                || ! collect($allowed)->contains(fn (string $prefix) => str_starts_with($parts[1].'/'.$parts[2], $prefix))) {
                throw new RuntimeException('Unsafe file path in archive: '.$name);
            }
            $stream = $zip->getStream($name);
            if ($stream === false) {
                throw new RuntimeException('Cannot read archived file.');
            }
            try {
                Storage::disk($parts[1])->put($parts[2], $stream);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
        }
    }

    /** @param array<string, mixed> $values */
    private function audit(string $action, BackupRecord $record, ?string $userId, array $values): void
    {
        // Runs on the queue (no authenticated user), so the actor is passed explicitly.
        AuditLog::create([
            'user_id' => $userId,
            'action' => $action,
            'module' => 'backups',
            'record_id' => $record->id,
            'new_values' => $values,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'created_at' => now(),
        ]);
    }
}
