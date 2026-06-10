# Security Fix Checklist

Status legend: ✅ implemented & verified in code/tests · 🛠️ operational (do at deploy) · ℹ️ informational

## Authentication
- ✅ Login throttled per email+IP (configurable 3–20/min via `security.login_attempts`)
- ✅ Registration throttled (`throttle:5,1`); OTP verify (`throttle:10,1`); password reset (`throttle:5,1`)
- ✅ Field-uniqueness probe endpoint throttled (`throttle:20,1`) — **F-01 fix**
- ✅ Weak passwords rejected (`PasswordPolicyService`, separate admin/applicant policies)
- ✅ Inactive/suspended users cannot log in; session invalidated on detection
- ✅ Admin users blocked from applicant area and vice versa
- 🛠️ Confirm strong `ADMIN_NAME`/`ADMIN_EMAIL`/`ADMIN_PASSWORD` in production `.env` before seeding

## Authorization
- ✅ Every admin route guarded by `permission:` / `role_or_permission:` middleware
- ✅ Policies scope applicant data by `applicant_id` (no cross-applicant IDOR)
- ✅ `Gate::before` super-admin bypass; `Gate::after` blocks applicants from non-`applicant.*` abilities
- ✅ Privilege-escalation guard: non-super-admin cannot assign `super_admin` role
- ✅ Last active Super Admin cannot be deleted/deactivated (policy + model guards)
- ✅ Vacancy with applications cannot be deleted

## Session security
- ✅ `session()->regenerate()` after login (anti-fixation)
- ✅ `invalidate()` + `regenerateToken()` on logout and on inactive-account detection
- ✅ Idle session timeout enforced (`EnforceSessionTimeout`, configurable 5–1440 min)
- ✅ `SESSION_ENCRYPT=true`, `SESSION_HTTP_ONLY=true`, `SESSION_SAME_SITE=lax`
- 🛠️ `SESSION_SECURE_COOKIE=true` requires HTTPS in production (already in `.env.example`)

## Input validation
- ✅ All writes go through Form Requests / explicit `validate()` with typed rules
- ✅ Mass assignment blocked (unknown keys stripped; role/status hardcoded server-side)
- ✅ Enum/status values validated via `Rule::enum` / `in:` constraints
- ✅ Settings allow-lists constrain file types, locales, colors, weights

## XSS
- ✅ Blade auto-escaping for all user text fields
- ✅ Rich HTML (announcements) sanitized with HTMLPurifier on **both** write and read
- ✅ `URI.AllowedSchemes` excludes `javascript:`/`data:`; only http/https/mailto/tel allowed
- ✅ `{!! !!}` sites audited — user-facing ones all pass through the sanitizer
- 🛠️ Set `CSP_ENFORCE=true` after validating the report-only CSP against real traffic

## CSRF
- ✅ All state-changing routes in the `web` group (VerifyCsrfToken applied)
- ✅ Stale-token (419) handled gracefully with redirect to login

## SQL injection
- ✅ Eloquent / query-builder with bound parameters throughout
- ✅ `whereRaw` for JSON search uses `?` placeholders (no string interpolation)

## File upload security
- ✅ `SafeUploadRule` hard-denies svg/html/xml/js/php (by extension **and** MIME) on every upload path
- ✅ Size limits enforced from settings; MIME allow-list per upload type
- ✅ Stored under UUID filenames (no path traversal, no predictable names)

## Private storage
- ✅ Applicant/application documents stored on private `local` disk, never `public`
- ✅ Downloads served only via authenticated, policy-checked controllers
- ✅ Admin sensitive-data access writes an audit-log entry

## Business logic
- ✅ Duplicate application prevented (transaction + DB unique constraint + race-condition catch)
- ✅ Deadline/closed/draft checks enforced at controller, policy, and action layers (defense in depth)
- ✅ Document replacement blocked after deadline

## Admin permissions
- ✅ Granular Spatie permissions per role; settings update requires `settings.manage` **and** `settings.security`
- ✅ Report/screening export and screening decisions gated by permission
- ✅ `super_admin` role protected from permission edits via `RoleController`

## Audit logging
- ✅ Login, user CRUD, settings changes, screening decisions/reversals, sensitive-data views logged
- ✅ Audit logs immutable via app (no update route); deletion requires super_admin + `audit.delete`

## Backup
- 🛠️ Schedule encrypted DB + `storage/app` backups; document restore procedure (see `docs/`)

## Deployment hardening
- ✅ `.env.example` ships `APP_ENV=production`, `APP_DEBUG=false`, `LOG_LEVEL=warning`
- ✅ HTTPS forced (`URL::forceScheme`) + HSTS in production
- ✅ Security headers middleware active globally
- 🛠️ Run `php artisan optimize` (config/route/view cache) on deploy
- 🛠️ Ensure `storage/` and `bootstrap/cache/` are writable; web root points at `public/` only

## Monitoring
- ✅ CSP report endpoint configurable (`CSP_REPORT_URI`)
- 🛠️ Forward `storage/logs/laravel.log` to centralized logging; alert on repeated 401/403/429

## Vulnerability management
- ✅ `composer audit` — no advisories
- 🛠️ `npm audit fix` — resolve dev-only `shell-quote`/`concurrently` advisory (**F-02**, not shipped)
- 🛠️ Re-run `composer audit` / `npm audit` on a schedule

## Availability / reliability
- ✅ PDF exports capped at 500 rows (memory-exhaustion guard); Excel exports stream via chunks
- ✅ Rate limits across auth, registration, reset, tracking, application submit, and field-probe endpoints
- ✅ `/up` health endpoint exposed for uptime checks
