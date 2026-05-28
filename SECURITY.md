# Security Guide

## Overview

This document covers the security model and controls implemented in the Jobs Recruitment Portal. It is intended for developers, operations staff, and auditors.

---

## Production Environment

HTTPS is required. Terminate TLS at the load balancer or web server and ensure Laravel receives the correct secure scheme headers.

never commit production .env files. Generate `APP_KEY` with `php artisan key:generate` on the target environment and rotate it only with a planned session/encrypted-data migration.

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
SESSION_ENCRYPT=true
SESSION_LIFETIME=120

BCRYPT_ROUNDS=12

QUEUE_CONNECTION=redis
CACHE_STORE=redis
```

---

## Authentication

### Password Hashing

All passwords are hashed using Bcrypt via Laravel's `password` cast (`'hashed'`). The `BCRYPT_ROUNDS` environment variable is set to `12` in `.env.example`, which is the recommended production value.

### Password Strength

- **Registration:** Minimum 8 characters, mixed case, numbers, and symbols (enforced via `Password::min(8)->mixedCase()->numbers()->symbols()`).
- **Password reset (both admin and applicant):** Same strong rule enforced on the reset form.
- **Admin user creation:** Minimum 8 characters, mixed case, and numbers.

### Login Throttling

All login and password-reset routes are protected with Laravel's built-in `throttle` middleware:

| Route | Limit |
| --- | --- |
| Applicant login | 5 requests / minute |
| Admin login | 5 requests / minute |
| OTP send | 5 requests / minute |
| OTP verify | 10 requests / minute |
| Password reset | 5 requests / minute |
| Application tracking | 10 requests / minute |

### Session Security

- **Session regeneration** on successful login (both applicant and admin).
- **Session invalidation** on logout (`invalidate()` + `regenerateToken()`).
- **Session driver:** `database` by default; use `redis` in production.
- **Secure cookie:** `SESSION_SECURE_COOKIE=true` must be set in production.
- **HTTP-only flag:** `SESSION_HTTP_ONLY=true` (default).
- **Same-site attribute:** `SESSION_SAME_SITE=lax` (default).

### OTP-Based Password Reset

Password reset uses a custom OTP flow:

1. A 6-digit OTP is generated with `random_int()` and stored as a **bcrypt hash** (not plaintext).
2. The OTP expires in 10 minutes.
3. Old OTPs are deleted before issuing a new one (no OTP accumulation).
4. After OTP verification, a random 40-character token is stored in the session.
5. The reset form validates this token with `hash_equals()` (timing-safe comparison).
6. Success messages are shown regardless of whether the email exists (prevents enumeration).

---

## Authorization

### Role-Based Access Control (RBAC)

Authorization uses **Spatie Laravel Permission** with the following roles:

| Role | Description |
| --- | --- |
| `super_admin` | Full access; can delete other super admins when more than one exists |
| `admin` | Full admin panel access except super admin management |
| `hr_manager` | Vacancies, applications, scheduling |
| `hr_officer` | Read-only applications and reports |
| `screening_officer` | Screening assigned applications only |
| `exam_officer` | Exam scheduling and results |
| `interview_officer` | Interview scheduling and results |
| `applicant` | Applicant portal only; blocked from admin panel |

### Route-Level Guards

- All admin routes are behind the `admin` middleware (`AdminAuthenticate`).
- The `admin` middleware verifies: user is authenticated, not an applicant, is active, and has at least one role.
- Fine-grained permissions are checked per route with `permission:` and `role_or_permission:` middleware.
- Applicant routes use the `applicant` middleware (`EnsureIsApplicant`), which also enforces email verification.

### Policy-Level Authorization

- `ApplicationPolicy`: applicants can only view/edit their own applications.
- `ApplicationDocumentPolicy`: applicants can only download their own documents; document replacement requires ownership + open deadline.
- `UserPolicy`: prevents deletion of the last active super admin; prevents self-deletion; non-super-admins cannot modify super admin accounts.

### Super Admin Protection

- A super admin cannot be deleted if they are the only active super admin.
- Non-super-admin users cannot delete or update super admin accounts.
- The `destroy` action in `UserController` is policy-gated.

---

## Admin Accounts

Production admin seeding requires these environment variables:

```env
ADMIN_NAME=
ADMIN_EMAIL=
ADMIN_PASSWORD=
```

`ADMIN_PASSWORD` must be strong: at least 12 characters, mixed case, numbers, and symbols. Local default credentials are development-only and must never be used in production.

Local development defaults (never use in production):

| Role | Email | Default Password |
| --- | --- | --- |
| Super Admin | `superadmin@jobs.local` | SuperAdmin@123 |
| Admin | `admin@jobs.local` | HrAdmin@123 |
| Screening Officer | `screening@jobs.local` | Screening@123 |

---

## Uploads and Private File Storage

Uploaded applicant documents are stored on the private `local` disk (`storage/app/`) and must not be exposed through the web server. Only `storage/app/public/` may be linked to `public/storage`.

Document downloads are served exclusively through `DocumentDownloadController` and `AdminDocumentDownloadController`, both of which check authorization policies before streaming.

### Allowed File Types

| Upload context | Allowed types | Max size |
| --- | --- | --- |
| Applicant profile photo | jpg, jpeg, png | 2 MB |
| Applicant registration documents | pdf | 2 MB |
| Application documents (per vacancy) | Configured per vacancy document | 2 MB (default) |
| Org logo | jpg, jpeg, png, webp | 1 MB |
| Org favicon | ico, png, jpg, jpeg, webp | 512 KB |
| Hero slider image | jpg, jpeg, png, webp | 3 MB |

SVG uploads are not allowed anywhere in the system to prevent embedded XSS attacks.

---

## XSS Prevention

- All Blade template output uses `{{ }}` (HTML-escaped) by default.
- Unescaped `{!! !!}` output is used only in controlled contexts:
  - Announcement content — passed through `HTMLPurifier` before storage (XSS stripped).
  - SVG icon variables sourced from controller-side arrays, not user input.
  - `nl2br(e($value))` — explicitly HTML-escaped before line-break insertion.
- Hero slider `button_link` is validated to only allow `http://` or `https://` schemes via the `HttpOrHttpsUrl` rule, blocking `javascript:`, `data:`, `ftp://`, and protocol-relative URLs.

---

## CSRF Protection

CSRF protection is enabled globally for all web routes via Laravel's default middleware stack. All state-changing forms include `@csrf`. API-style POST routes not needing cookies are excluded if created under an `api` prefix.

---

## SQL Injection

- All database queries use Eloquent ORM or parameterized query builder bindings.
- Search parameters (e.g., `%$search%`) are passed as bindings, not string-interpolated into SQL.
- Filter parameters (vacancy ID, status, role) are validated before use.

---

## Security Headers

Consider adding these headers via Nginx or a middleware in production:

```text
X-Content-Type-Options: nosniff
X-Frame-Options: SAMEORIGIN
X-XSS-Protection: 1; mode=block
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: camera=(), microphone=(), geolocation=()
Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-{nonce}'; ...
```

---

## Dependency Security

Run before each deployment:

```bash
composer audit
npm audit --omit=dev
```

Address any HIGH or CRITICAL advisories before going live.

---

## Operations Security

- Run queue workers continuously for notifications and exports.
- Run the Laravel scheduler every minute if scheduled tasks are added.
- Back up the database and `storage/app/` (private disk) regularly.
- Restrict file permissions to the web user for `storage/` and `bootstrap/cache/`.
- Rotate local/default credentials after any staging import.
- Configure log rotation (`/etc/logrotate.d/jobs`).
- Firewall: allow only ports 80, 443, and 22.
- Bind Redis to `127.0.0.1` and set `requirepass`.

---

## Reporting Security Issues

Report security vulnerabilities directly to the maintainer at the address in `composer.json` or via private issue reporting. Do not open public GitHub issues for security vulnerabilities.
