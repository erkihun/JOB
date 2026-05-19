# Security Hardening

## Production Environment

Use production settings before the application is reachable from the internet:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
LOG_LEVEL=warning

SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
CACHE_STORE=redis
```

HTTPS is required. Terminate TLS at the load balancer or web server and ensure Laravel receives the correct secure scheme headers.

never commit production .env files. Generate `APP_KEY` with `php artisan key:generate` on the target environment and rotate it only with a planned session/encrypted-data migration.

## Admin Accounts

Production admin seeding requires these environment variables:

```env
ADMIN_NAME=
ADMIN_EMAIL=
ADMIN_PASSWORD=
```

`ADMIN_PASSWORD` must be strong: at least 12 characters, mixed case, numbers, and symbols. Local default credentials are development-only and must never be used in production.

## Uploads And Private Files

Uploaded applicant documents are stored on the private `local` disk and must not be exposed through the web server. Only `storage/app/public` may be linked to `public/storage`.

SVG uploads are not allowed for organization logos or hero sliders. Allowed logo/image upload types are JPG, JPEG, PNG, and WebP where the feature already supports WebP.

Set web server upload limits to match application rules. Applicant documents are limited by the application to 2 MB unless a vacancy document type specifies a smaller allowed size.

## Operations

- Run queue workers continuously for notifications and exports.
- Run the Laravel scheduler every minute if scheduled tasks are added.
- Back up the database and `storage/app/private` regularly.
- Restrict file permissions to the web user for `storage` and `bootstrap/cache`.
- Rotate local/default credentials after any staging import.
- Keep `composer audit` and `npm audit --omit=dev` clean before deployment.
