# Deployment Checklist

## Prerequisites

Custom UI styling in this project uses **Tailwind CSS** and **Filament theming APIs** only. No external design-tool dependency is required for build or deployment.

- PHP 8.2+ with extensions: pdo, pdo_mysql, mbstring, openssl, tokenizer, xml, ctype, json, bcmath, fileinfo
- MySQL 8.0+ or MariaDB 10.6+
- Node.js 18+ and npm
- Redis (recommended for queue and cache)
- A web server: Nginx or Apache
- Composer 2.x

---

## 1. Clone and Install Dependencies

```bash
git clone <repo-url> /var/www/jobs
cd /var/www/jobs

composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

---

## 2. Environment Configuration

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and configure:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=jobs_db
DB_USERNAME=jobs_user
DB_PASSWORD=your-secure-password

CACHE_STORE=redis
SESSION_DRIVER=redis
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=smtp.your-provider.com
MAIL_PORT=587
MAIL_USERNAME=your@email.com
MAIL_PASSWORD=your-mail-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="Job Vacancy System"

FILESYSTEM_DISK=local

ADMIN_NAME="Production Super Admin"
ADMIN_EMAIL=admin@your-domain.com
ADMIN_PASSWORD=use-a-strong-unique-password
```

HTTPS is required in production. Configure TLS at Nginx/Apache or the load balancer before enabling `SESSION_SECURE_COOKIE=true`.

---

## 3. Database Setup

```bash
php artisan migrate --force
php artisan db:seed --class=RolesAndPermissionsSeeder
php artisan db:seed --class=AdminUserSeeder
php artisan db:seed --class=SettingsSeeder
```

In production, `AdminUserSeeder` creates a Super Admin only from `ADMIN_NAME`, `ADMIN_EMAIL`, and a strong `ADMIN_PASSWORD`. If those values are missing or weak, the seeder fails safely.

Default credentials (development only):
| Role | Email | Password |
|---|---|---|
| Super Admin | superadmin@jobs.local | SuperAdmin@123 |
| Admin | admin@jobs.local | HrAdmin@123 |
| Screening Officer | screening@jobs.local | Screening@123 |

Never use local default credentials in production.

---

## 4. Storage

```bash
# Ensure storage is linked for public assets (logos, sliders)
php artisan storage:link

# Set correct permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

**Private document storage** (`storage/app/` = `local` disk) must never be web-accessible.
Applicant documents are served exclusively through `DocumentDownloadController` with auth checks.

Do NOT expose `storage/app/` via the web server. Only `storage/app/public/` is linked to `public/storage/`.

Set Nginx `client_max_body_size` or Apache `LimitRequestBody` to a value compatible with the application upload limit. Applicant uploads are intentionally capped at 2 MB unless configured per vacancy document.

---

## 5. Optimization

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan icons:cache    # Filament icon cache
php artisan filament:cache-components
```

---

## 6. Queue Worker

Using Supervisor (recommended):

```ini
[program:jobs-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/jobs/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
directory=/var/www/jobs
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/jobs/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
supervisorctl reread
supervisorctl update
supervisorctl start jobs-worker:*
```

After each deployment:
```bash
php artisan queue:restart
```

Backups run as queued jobs (`RunBackupJob`, `RestoreBackupJob`, 1 attempt, up to 60 minutes each). A dedicated
worker keeps a long backup from delaying notifications. Set `BACKUP_QUEUE=backups` and add:

```ini
[program:jobs-backup-worker]
command=php /var/www/jobs/artisan queue:work redis --queue=backups --tries=1 --timeout=3700 --max-time=3600
directory=/var/www/jobs
autostart=true
autorestart=true
user=www-data
numprocs=1
stopwaitsecs=3700
stdout_logfile=/var/www/jobs/storage/logs/backup-worker.log
```

The queue connection's `retry_after` must be **greater than 3700 seconds** (`REDIS_QUEUE_RETRY_AFTER=3800`, or
`DB_QUEUE_RETRY_AFTER=3800` with the database driver; the default is 90). Otherwise a backup that runs longer
than that is handed to a second worker while the first is still running. The job ignores such a redelivery,
but it still occupies a worker.

---

## 7. Task Scheduler

Add to crontab (`crontab -e` as www-data or root):

```cron
* * * * * cd /var/www/jobs && php artisan schedule:run >> /dev/null 2>&1
```

Scheduled tasks (`routes/console.php`, all `withoutOverlapping()->onOneServer()`):

| Command | Frequency | Purpose |
| --- | --- | --- |
| `backups:dispatch-due` | every minute | queues database/document backups whose configured slot has come (each slot once) |
| `recruitment:sync-statuses` | every 15 minutes | records recruitment announcements opening/closing |

`onOneServer()` needs a cache store that supports locks (database, redis or memcached).

---

## 8. Nginx Configuration

```nginx
server {
    listen 80;
    server_name your-domain.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name your-domain.com;

    root /var/www/jobs/public;
    index index.php;

    ssl_certificate /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;

    # HSTS — instruct browsers to use HTTPS only for 1 year (includeSubDomains optional)
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    # Security headers (belt-and-suspenders alongside Laravel SecurityHeaders middleware)
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Permissions-Policy "camera=(), microphone=(), geolocation=()" always;

    # Disable dangerous HTTP methods (TRACE / TRACK enable cross-site tracing)
    if ($request_method ~* "^(TRACE|TRACK)$") {
        return 405;
    }

    # Deny access to private storage
    location /storage/app {
        deny all;
        return 403;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

---

## 9. Database & Document Backups

Backups are built in. They are configured under **Admin → Settings → Backup** (`/admin/settings/backups`) and
run by the scheduler and queue worker above. No extra package is required for local backups.

### What is backed up

| Backup | Contents |
| --- | --- |
| **Database** | Full dump of the default connection: `mysqldump --single-transaction --routines --triggers` (MySQL/MariaDB), `pg_dump` (PostgreSQL) or `VACUUM INTO` (SQLite) |
| **Documents** | Selectable groups: applicant private documents (`storage/app/private/applicant-documents`, `applications`), profile photos, logos (`storage/app/public/org`, `institutions`), hero images, exported reports. Temp uploads, cache and log files are always excluded. |

Each backup is one ZIP with a `manifest.json`. It can optionally be compressed and/or encrypted.
Every run is recorded in `backup_records` with these fields:

- type, destination and storage path;
- size and SHA-256 checksum;
- status, started/completed times and who started it;
- the failure message, when it failed.

### Configuration (.env — secrets live here only)

```dotenv
BACKUP_DISK=backup_local            # local destination → storage/app/backups (never web-served)
BACKUP_ENCRYPTION_KEY=base64:...    # php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
BACKUP_QUEUE=backups
BACKUP_MYSQLDUMP_PATH=mysqldump     # full path if not on PATH (XAMPP: C:/xampp/mysql/bin/mysqldump.exe)
BACKUP_MYSQL_PATH=mysql
# Off-site, S3-compatible destination (AWS S3, MinIO, Wasabi, …):
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=
AWS_BUCKET=
AWS_ENDPOINT=                       # for non-AWS S3-compatible providers
```

The S3 destination also needs `composer require league/flysystem-aws-s3-v3`. Until it is installed and `AWS_*` is
set, the option is disabled in the UI. System Settings stores only non-secret preferences: schedule, retention,
destination name and what to include.

- **Encryption:** AES-256-GCM in authenticated 1 MB chunks. A wrong key, a modified file, reordered chunks or a
  truncated file are all rejected. Keep a copy of `BACKUP_ENCRYPTION_KEY` offline (e.g. a password manager or
  sealed envelope): **an encrypted backup cannot be restored without it.** Rotating the key does not re-encrypt
  old backups, so keep the old key for as long as its backups are retained.
- **Retention:** after each successful backup, copies older than *Retention (days)* or beyond *Maximum backup
  copies* are deleted. The newest successful copy is always kept. Pre-restore copies are never pruned
  automatically. The history row stays, marked "Removed by retention".
- **Schedule:** *hourly* (at the chosen minute), *daily*, *weekly* (Monday) or *monthly* (1st). Times are in
  `APP_TIMEZONE`. Each slot is queued at most once. A slot missed while the server was down is caught up on the
  next scheduler run. A backup that has been queued or running for more than 3 hours is marked failed.

### Restore procedure

Restore and delete are granted to **super_admin only** (`backups.restore`, `backups.delete`).

1. Prefer the server shell for database restores:
   ```bash
   php artisan backups:list --type=database
   php artisan backups:restore <backup-id>      # asks you to type RESTORE
   ```
   From the UI: **Backup history → Restore**. You must type `RESTORE`, enter your current password and your
   authenticator code (if MFA is enabled). The restore then runs on the queue.
2. The restore runs these steps in order:
   1. downloads the artifact and **verifies its SHA-256 checksum** (a mismatch aborts before anything changes);
   2. decrypts and authenticates it, and checks the manifest type;
   3. takes a **pre-restore backup** of the current data (rollback point; the restore aborts if this fails);
   4. puts the site into **maintenance mode**;
   5. restores, re-inserts the backup-history rows, writes the audit log entry, and brings the site back up.
3. A database restore replaces the whole database with the dump, so audit-log rows written after that backup
   are lost. The `backup_restored` audit entry itself is written after the restore.

   A document restore overwrites the archived files. Files uploaded after the backup are kept.
4. To undo a restore, restore the "Pre-restore copy" listed in the history.

### Off-site recommendation

Local backups protect against mistakes, not against losing the server. Use at least one of these:

- the S3-compatible destination (ideally a bucket with object lock/versioning, in another region or provider);
- a nightly `rsync`/`rclone` of `storage/app/backups` to another machine.

Test a restore on a staging server at least once a quarter.

---

## 10. Post-Deployment Steps

```bash
# After every deployment
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
supervisorctl restart jobs-worker:*
```

---

## 11. High-Concurrency / 30,000+ Applicant Configuration

### PHP-FPM tuning (`/etc/php/8.2/fpm/pool.d/www.conf`)

```ini
pm = dynamic
pm.max_children = 32
pm.start_servers = 8
pm.min_spare_servers = 4
pm.max_spare_servers = 16
pm.max_requests = 500
```

### Nginx upload limits (for document uploads up to 2 MB)

```nginx
client_max_body_size 10M;
client_body_timeout 60s;
fastcgi_read_timeout 120s;
```

### Production `.env` recommendations for scale

```env
# Redis for cache and queue (strongly recommended over database driver)
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

# Secure sessions
SESSION_SECURE_COOKIE=true
SESSION_ENCRYPT=true
SESSION_LIFETIME=120

# Bcrypt cost (already set, keep at 12)
BCRYPT_ROUNDS=12

# Disable debug
APP_DEBUG=false
APP_ENV=production

# Log at warning level in production (reduces I/O)
LOG_LEVEL=warning
```

### Queue workers for notifications (Supervisor)

Increase to 4 workers during high-concurrency application periods:

```ini
numprocs=4
```

### Dashboard metric caching (optional, high-traffic periods)

If admin dashboard becomes slow under load, cache the aggregate stats:

```bash
# In a scheduled command or middleware, cache for 60 seconds:
Cache::remember('dashboard_stats', 60, fn() => [...]);
```

### Prove capacity before go-live

```bash
# 1. Apply all migrations (includes performance indexes)
php artisan migrate --force

# 2. Seed 30k dataset (staging only)
php artisan recruitment:seed-load-test --applicants=30000 --vacancies=20 --applications=30000

# 3. Run load tests — see LOAD_TESTING.md
k6 run -e BASE_URL=https://staging.your-domain.com load-tests/k6/vacancy-browse.js
k6 run -e BASE_URL=https://staging.your-domain.com load-tests/k6/spike-test.js

# 4. Run automated test suite
php artisan test
```

---

## 12. Security Checklist

- [ ] `APP_DEBUG=false` in production
- [ ] `APP_KEY` is set and unique
- [ ] Database credentials use a dedicated user (not root)
- [ ] `storage/app/` is NOT web-accessible
- [ ] HTTPS enforced (redirect HTTP → HTTPS)
- [ ] Default admin passwords changed
- [ ] Rate limiting active on login/register/apply routes
- [ ] File upload validation enforced (MIME + size)
- [ ] Session driver set to `redis` (not `file`) in production
- [ ] Queue driver set to `redis` (not `sync`) in production
- [ ] Backups configured (Settings → Backup), `BACKUP_ENCRYPTION_KEY` set and stored offline, off-site copy configured, a test restore done on staging
- [ ] Log rotation configured (`/etc/logrotate.d/jobs`)
- [ ] Firewall: only 80, 443, and 22 open
- [ ] Redis protected (bind 127.0.0.1, requirepass set)
