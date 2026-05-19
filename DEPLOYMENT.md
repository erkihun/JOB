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

---

## 7. Task Scheduler

Add to crontab (`crontab -e` as www-data or root):

```cron
* * * * * cd /var/www/jobs && php artisan schedule:run >> /dev/null 2>&1
```

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

## 9. Database Backups

**Automated daily backup with mysqldump:**

```bash
# /etc/cron.daily/jobs-backup
#!/bin/bash
BACKUP_DIR="/var/backups/jobs"
DATE=$(date +%Y%m%d_%H%M%S)
mkdir -p "$BACKUP_DIR"
mysqldump -u jobs_user -p'your-password' jobs_db | gzip > "$BACKUP_DIR/db_$DATE.sql.gz"
# Keep last 30 days
find "$BACKUP_DIR" -name "db_*.sql.gz" -mtime +30 -delete
```

**Storage backup:**

```bash
# Include in your backup script
tar -czf "$BACKUP_DIR/storage_$DATE.tar.gz" /var/www/jobs/storage/app/
```

Recommended: use `spatie/laravel-backup` for automated cloud backups.

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
- [ ] Backups configured and tested
- [ ] Log rotation configured (`/etc/logrotate.d/jobs`)
- [ ] Firewall: only 80, 443, and 22 open
- [ ] Redis protected (bind 127.0.0.1, requirepass set)
