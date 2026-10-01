<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\RunBackupJob;
use App\Models\BackupRecord;
use App\Models\Setting;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;
use ZipArchive;

/**
 * Database and document backups.
 *
 * Only non-secret preferences live in the settings table (`backup.{type}.*`).
 * The encryption key and storage credentials come from .env only
 * (BACKUP_ENCRYPTION_KEY, BACKUP_DISK, AWS_*) — see config/backup.php.
 *
 * Artifact format: a ZIP (manifest.json + database/ or files/{disk}/…),
 * optionally wrapped in the chunked AES-256-GCM format implemented by crypt().
 * The SHA-256 checksum of the stored artifact is kept on the BackupRecord and
 * verified before any restore.
 */
class BackupService
{
    public const TYPES = ['database', 'documents'];

    public const FREQUENCIES = ['hourly', 'daily', 'weekly', 'monthly'];

    public const DESTINATIONS = ['local', 's3'];

    public const DOCUMENT_GROUPS = ['private_documents', 'profile_photos', 'logos', 'hero_images', 'reports'];

    /** Folders of each document group, per filesystem disk. */
    public const DOCUMENT_FOLDERS = [
        'private_documents' => ['local' => ['applicant-documents', 'applications']],
        'profile_photos' => ['local' => ['applicants/photos'], 'public' => ['users/photos']],
        'logos' => ['public' => ['org', 'institutions']],
        'hero_images' => ['public' => ['hero-sliders']],
        'reports' => ['local' => ['reports']],
    ];

    /** Path segments that are never backed up (temporary uploads, caches, logs). */
    private const EXCLUDED_SEGMENTS = ['tmp', 'temp', 'cache', 'logs', 'backup-work'];

    /** A queued/running backup older than this is considered dead (worker crash). */
    public const STALE_AFTER_MINUTES = 180;

    private const MAGIC = 'JOBSBAK1';

    private const CHUNK_BYTES = 1048576;

    // ── Preferences (non-secret) ────────────────────────────────────────────

    /** @return array<string, mixed> */
    public static function defaults(): array
    {
        return [
            'enabled' => false, 'frequency' => 'daily', 'time' => '02:00',
            'retention_days' => 30, 'max_copies' => 30, 'destination' => 'local',
            'compress' => true, 'encrypt' => false, 'include_database' => true,
            'includes' => self::DOCUMENT_GROUPS,
        ];
    }

    public function preference(string $type, string $name): mixed
    {
        return Setting::get("backup.{$type}.{$name}", self::defaults()[$name] ?? null);
    }

    /** @return array<string, mixed> */
    public function preferences(string $type): array
    {
        return collect(array_keys(self::defaults()))
            ->mapWithKeys(fn (string $name) => [$name => $this->preference($type, $name)])
            ->all();
    }

    // ── Destinations & secrets (.env only) ──────────────────────────────────

    public function s3Available(): bool
    {
        return class_exists(AwsS3V3Adapter::class)
            && filled(config('filesystems.disks.s3.key'))
            && filled(config('filesystems.disks.s3.secret'))
            && filled(config('filesystems.disks.s3.region'))
            && filled(config('filesystems.disks.s3.bucket'));
    }

    public function disk(string $destination): string
    {
        return match ($destination) {
            // BACKUP_DISK lets operators point "local" at another non-public disk (e.g. a mounted volume).
            'local' => (string) config('backup.disk', 'backup_local'),
            's3' => $this->s3Available() ? 's3' : throw new RuntimeException(__('backups.s3_unavailable')),
            default => throw new RuntimeException('Invalid backup destination.'),
        };
    }

    public function encryptionKeyConfigured(): bool
    {
        try {
            $this->encryptionKey();

            return true;
        } catch (RuntimeException) {
            return false;
        }
    }

    public function encryptionKey(): string
    {
        $raw = (string) config('backup.encryption_key');
        $key = base64_decode(str_starts_with($raw, 'base64:') ? substr($raw, 7) : $raw, true);

        if (! is_string($key) || strlen($key) !== 32) {
            throw new RuntimeException(__('backups.key_missing'));
        }

        return $key;
    }

    // ── Scheduling ──────────────────────────────────────────────────────────

    /** The most recent scheduled moment at or before $now. */
    public function lastDueSlot(string $type, ?CarbonImmutable $now = null): CarbonImmutable
    {
        $now ??= CarbonImmutable::now();
        [$hour, $minute] = array_map('intval', explode(':', (string) $this->preference($type, 'time')) + [0, 0]);

        return match ((string) $this->preference($type, 'frequency')) {
            'hourly' => ($slot = $now->setTime($now->hour, $minute))->gt($now) ? $slot->subHour() : $slot,
            'weekly' => ($slot = $now->startOfWeek(CarbonImmutable::MONDAY)->setTime($hour, $minute))->gt($now) ? $slot->subWeek() : $slot,
            'monthly' => ($slot = $now->startOfMonth()->setTime($hour, $minute))->gt($now) ? $slot->subMonthNoOverflow() : $slot,
            default => ($slot = $now->setTime($hour, $minute))->gt($now) ? $slot->subDay() : $slot,
        };
    }

    public function nextRunAt(string $type, ?CarbonImmutable $now = null): ?CarbonImmutable
    {
        if (! $this->preference($type, 'enabled')) {
            return null;
        }

        $last = $this->lastDueSlot($type, $now);

        return match ((string) $this->preference($type, 'frequency')) {
            'hourly' => $last->addHour(),
            'weekly' => $last->addWeek(),
            'monthly' => $last->addMonthNoOverflow(),
            default => $last->addDay(),
        };
    }

    /** Mark the current slot as handled so enabling a schedule does not fire immediately. */
    public function markSlotHandled(string $type, ?CarbonImmutable $slot = null): void
    {
        Setting::set("backup.{$type}.last_slot", ($slot ?? $this->lastDueSlot($type))->format('Y-m-d H:i'), 'string', 'backup');
    }

    /** Due when the latest slot has not been handled yet. */
    public function isDue(string $type, ?CarbonImmutable $now = null): bool
    {
        return $this->preference($type, 'enabled')
            && (string) Setting::get("backup.{$type}.last_slot", '') < $this->lastDueSlot($type, $now)->format('Y-m-d H:i');
    }

    /** Fail backups whose worker died, so they no longer block new runs. */
    public function failStale(): int
    {
        return BackupRecord::query()
            ->whereIn('status', ['queued', 'running'])
            ->where('updated_at', '<', now()->subMinutes(self::STALE_AFTER_MINUTES))
            ->update(['status' => 'failed', 'failure_message' => 'Timed out: no worker finished this backup.', 'completed_at' => now()]);
    }

    // ── Running ─────────────────────────────────────────────────────────────

    /**
     * Queue one backup. Returns the already queued/running backup of the same
     * type instead of creating a duplicate.
     */
    public function queue(string $type, ?string $userId = null): BackupRecord
    {
        if (! in_array($type, self::TYPES, true)) {
            throw new RuntimeException('Invalid backup type.');
        }

        $record = DB::transaction(function () use ($type, $userId): BackupRecord {
            $active = BackupRecord::query()->where('type', $type)->whereIn('status', ['queued', 'running'])->lockForUpdate()->first();
            if ($active) {
                return $active;
            }

            $prefs = $this->preferences($type);
            if ($prefs['encrypt']) {
                $this->encryptionKey();
            }

            return BackupRecord::create([
                'type' => $type,
                'destination' => $prefs['destination'],
                'disk' => $this->disk((string) $prefs['destination']),
                'status' => 'queued',
                'initiated_by' => $userId,
                'options' => [
                    'encrypt' => (bool) $prefs['encrypt'],
                    'compress' => (bool) $prefs['compress'],
                    'include_database' => (bool) $prefs['include_database'],
                    'includes' => array_values((array) $prefs['includes']),
                    'scheduled' => $userId === null,
                ],
            ]);
        });

        if ($record->wasRecentlyCreated) {
            RunBackupJob::dispatch($record->id)->onQueue((string) config('backup.queue', 'default'));
        }

        return $record;
    }

    public function run(BackupRecord $record): void
    {
        if ($record->status !== 'queued') {
            return;
        }
        $record->update(['status' => 'running', 'started_at' => now()]);
        $work = $this->workspace();

        try {
            $options = (array) $record->options;
            $archive = $work.'/backup.zip';
            $zip = new ZipArchive;
            if ($zip->open($archive, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Cannot create backup archive.');
            }
            $files = 0;
            try {
                $zip->addFromString('manifest.json', json_encode([
                    'type' => $record->type,
                    'backup_id' => $record->id,
                    'created_at' => now()->toIso8601String(),
                    'app' => config('app.name'),
                    'database_driver' => config('database.connections.'.config('database.default').'.driver'),
                ], JSON_THROW_ON_ERROR));

                if ($record->type === 'database' && ($options['include_database'] ?? true)) {
                    [$file, $name] = $this->dumpDatabase($work);
                    $zip->addFile($file, 'database/'.$name);
                    $files++;
                }
                if ($record->type === 'documents') {
                    $files += $this->addDocuments($zip, (array) ($options['includes'] ?? []));
                }

                // "Compress" off = store entries uncompressed (faster, larger).
                $method = ($options['compress'] ?? true) ? ZipArchive::CM_DEFLATE : ZipArchive::CM_STORE;
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $zip->setCompressionIndex($i, $method);
                }
            } finally {
                $zip->close();
            }

            $source = $archive;
            if ($options['encrypt'] ?? false) {
                $source = $work.'/backup.enc';
                $this->crypt($archive, $source, false);
            }

            $extension = ($options['encrypt'] ?? false) ? 'zip.enc' : 'zip';
            $path = 'backups/'.$record->type.'/'.now()->format('Y/m').'/'.$record->type.'-'.now()->format('Ymd-His').'-'.Str::lower(Str::random(6)).'.'.$extension;
            $stream = fopen($source, 'rb');
            if ($stream === false) {
                throw new RuntimeException('Cannot read backup artifact.');
            }
            try {
                if (! Storage::disk($record->disk)->put($path, $stream)) {
                    throw new RuntimeException('Backup upload failed.');
                }
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            $record->update([
                'status' => 'success', 'path' => $path, 'size' => filesize($source),
                'checksum' => hash_file('sha256', $source), 'completed_at' => now(),
                'options' => [...$options, 'files' => $files],
            ]);
            $this->prune($record->type, $record->id);
        } catch (Throwable $e) {
            $record->update(['status' => 'failed', 'failure_message' => mb_substr($e->getMessage(), 0, 1000), 'completed_at' => now()]);

            throw $e;
        } finally {
            $this->cleanWorkspace($work);
        }
    }

    public function workspace(): string
    {
        $work = storage_path('app/backup-work/'.Str::uuid());
        if (! is_dir($work) && ! mkdir($work, 0700, true) && ! is_dir($work)) {
            throw new RuntimeException('Cannot create backup workspace.');
        }

        return $work;
    }

    public function cleanWorkspace(string $work): void
    {
        foreach (glob($work.'/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($work);
    }

    /** @return array{0: string, 1: string} */
    private function dumpDatabase(string $work): array
    {
        $name = config('database.default');
        $db = config("database.connections.{$name}");

        if ($db['driver'] === 'sqlite') {
            if ($db['database'] === ':memory:') {
                throw new RuntimeException('Cannot back up an in-memory database.');
            }
            $file = $work.'/database.sqlite';
            DB::connection($name)->statement("VACUUM INTO '".str_replace("'", "''", $file)."'");

            return [$file, 'database.sqlite'];
        }

        $file = $work.'/database.sql';
        if ($db['driver'] === 'pgsql') {
            $command = [config('backup.pg_dump', 'pg_dump'), '--no-owner', '--no-acl', '--clean', '--if-exists', '--file='.$file,
                '--host='.$db['host'], '--port='.(string) $db['port'], '--username='.$db['username'], $db['database']];
            $env = ['PGPASSWORD' => (string) $db['password']];
        } elseif (in_array($db['driver'], ['mysql', 'mariadb'], true)) {
            $command = [config('backup.mysqldump', 'mysqldump'), '--single-transaction', '--routines', '--triggers', '--add-drop-table',
                '--result-file='.$file, '--host='.$db['host'], '--port='.(string) $db['port'], '--user='.$db['username'], $db['database']];
            // Password via environment, never on the command line (visible in process lists).
            $env = ['MYSQL_PWD' => (string) $db['password']];
        } else {
            throw new RuntimeException('Unsupported database driver.');
        }

        (new Process($command, base_path(), $env, null, 3600))->mustRun();

        return [$file, 'database.sql'];
    }

    private function addDocuments(ZipArchive $zip, array $includes): int
    {
        $count = 0;
        foreach (array_intersect(self::DOCUMENT_GROUPS, $includes) as $group) {
            foreach (self::DOCUMENT_FOLDERS[$group] as $disk => $directories) {
                foreach ($directories as $directory) {
                    foreach (Storage::disk($disk)->allFiles($directory) as $path) {
                        if ($this->isExcluded($path)) {
                            continue;
                        }
                        $zip->addFile(Storage::disk($disk)->path($path), 'files/'.$disk.'/'.$path);
                        $count++;
                    }
                }
            }
        }

        return $count;
    }

    public function isExcluded(string $path): bool
    {
        $segments = explode('/', strtolower(str_replace('\\', '/', $path)));

        return array_intersect($segments, self::EXCLUDED_SEGMENTS) !== []
            || str_ends_with($path, '.log') || str_ends_with($path, '.tmp')
            || basename($path) === '.gitignore';
    }

    // ── Encryption (AES-256-GCM, chunked, authenticated) ────────────────────

    /**
     * Encrypt or decrypt a file. Format: "JOBSBAK1" + 4-byte random nonce prefix,
     * then frames of [uint32 length][16-byte tag][ciphertext]. Each frame's nonce
     * is prefix + 64-bit counter, and the frame index plus a "final" flag are
     * authenticated, so reordering, truncation or appended data is rejected.
     */
    public function crypt(string $source, string $destination, bool $decrypt): void
    {
        $key = $this->encryptionKey();
        $in = fopen($source, 'rb');
        $out = fopen($destination, 'wb');
        if ($in === false || $out === false) {
            throw new RuntimeException('Cannot open backup encryption stream.');
        }

        try {
            $decrypt ? $this->decryptStream($in, $out, $key) : $this->encryptStream($in, $out, $key);
        } finally {
            fclose($in);
            fclose($out);
        }
    }

    /** @param resource $in @param resource $out */
    private function encryptStream($in, $out, string $key): void
    {
        $prefix = random_bytes(4);
        fwrite($out, self::MAGIC.$prefix);
        $index = 0;
        $chunk = (string) fread($in, self::CHUNK_BYTES);

        do {
            $next = (string) fread($in, self::CHUNK_BYTES);
            $final = $next === '';
            $tag = '';
            $cipher = openssl_encrypt($chunk, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $prefix.pack('J', $index), $tag, $this->aad($index, $final), 16);
            if ($cipher === false) {
                throw new RuntimeException('Backup encryption failed.');
            }
            fwrite($out, pack('N', strlen($cipher)).$tag.$cipher);
            $chunk = $next;
            $index++;
        } while (! $final);
    }

    /** @param resource $in @param resource $out */
    private function decryptStream($in, $out, string $key): void
    {
        $header = fread($in, 12);
        if (! is_string($header) || strlen($header) !== 12 || ! str_starts_with($header, self::MAGIC)) {
            throw new RuntimeException('Not an encrypted backup.');
        }
        $prefix = substr($header, 8, 4);
        $index = 0;

        while (true) {
            $length = fread($in, 4);
            if ($length === '' || $length === false) {
                throw new RuntimeException('Encrypted backup is truncated.');
            }
            $size = unpack('N', $length)[1] ?? 0;
            if ($size < 0 || $size > self::CHUNK_BYTES + 32) {
                throw new RuntimeException('Invalid encrypted frame.');
            }
            $tag = $this->readExactly($in, 16);
            $cipher = $size > 0 ? $this->readExactly($in, $size) : '';
            $final = feof($in) || $this->atEnd($in);

            $plain = openssl_decrypt($cipher, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $prefix.pack('J', $index), $tag, $this->aad($index, $final));
            if ($plain === false) {
                throw new RuntimeException('Backup authentication failed (wrong key or tampered file).');
            }
            fwrite($out, $plain);
            if ($final) {
                return;
            }
            $index++;
        }
    }

    private function aad(int $index, bool $final): string
    {
        return self::MAGIC.pack('J', $index).($final ? "\x01" : "\x00");
    }

    /** @param resource $in */
    private function readExactly($in, int $bytes): string
    {
        $data = '';
        while (strlen($data) < $bytes) {
            $part = fread($in, $bytes - strlen($data));
            if ($part === false || $part === '') {
                throw new RuntimeException('Encrypted backup is truncated.');
            }
            $data .= $part;
        }

        return $data;
    }

    /** @param resource $in */
    private function atEnd($in): bool
    {
        $position = ftell($in);
        $peek = fread($in, 1);
        if ($peek === '' || $peek === false) {
            return true;
        }
        fseek($in, $position);

        return false;
    }

    // ── Retention ───────────────────────────────────────────────────────────

    /** Apply retention; $keepId (the backup just made) is never removed. */
    private function prune(string $type, string $keepId): void
    {
        $days = max(1, (int) $this->preference($type, 'retention_days'));
        $copies = max(1, (int) $this->preference($type, 'max_copies'));

        BackupRecord::query()->where('type', $type)->where('status', 'success')
            ->where(fn ($q) => $q->whereNull('options->pre_restore')->orWhere('options->pre_restore', false))
            // Ordered UUIDs are time-ordered, so they break ties within the same second.
            ->orderByRaw('CASE WHEN id = ? THEN 0 ELSE 1 END', [$keepId])->latest('completed_at')->orderByDesc('id')->get()
            ->each(function (BackupRecord $record, int $index) use ($days, $copies): void {
                // The newest successful copy is always kept.
                if ($index === 0 || ($index < $copies && $record->completed_at?->greaterThan(now()->subDays($days)))) {
                    return;
                }
                $this->deleteArtifact($record);
                $record->update(['status' => 'pruned', 'path' => null]);
            });
    }

    public function deleteArtifact(BackupRecord $record): void
    {
        if ($record->path) {
            Storage::disk($record->disk)->delete($record->path);
        }
    }

    /** Bytes used by stored backups. */
    public function storageUsage(): int
    {
        return (int) BackupRecord::query()->where('status', 'success')->sum('size');
    }
}
