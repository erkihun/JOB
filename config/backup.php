<?php

declare(strict_types=1);

/*
 * Backup secrets and infrastructure live in .env only — never in the settings
 * table. Admin-editable, non-secret preferences (schedule, retention, what to
 * include) are stored as `backup.*` settings. See docs/DEPLOYMENT.md § Backups.
 */
return [
    // Disk used for the "local" destination (must NOT be a public disk).
    'disk' => env('BACKUP_DISK', 'backup_local'),

    // base64:-prefixed (or plain base64) 32-byte key for AES-256-GCM.
    // Generate: php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
    'encryption_key' => env('BACKUP_ENCRYPTION_KEY'),

    // Queue the backup jobs run on (a dedicated worker is recommended).
    'queue' => env('BACKUP_QUEUE', 'default'),

    // Dump/restore binaries (override when they are not on PATH, e.g. XAMPP).
    'mysqldump' => env('BACKUP_MYSQLDUMP_PATH', 'mysqldump'),
    'mysql' => env('BACKUP_MYSQL_PATH', 'mysql'),
    'pg_dump' => env('BACKUP_PG_DUMP_PATH', 'pg_dump'),
    'psql' => env('BACKUP_PSQL_PATH', 'psql'),
];
