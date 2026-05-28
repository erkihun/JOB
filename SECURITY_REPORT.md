# Security Audit Report

**Date:** 2026-05-28
**Scope:** Laravel 12 Jobs Recruitment Portal — full codebase review
**Auditor:** Security audit via Claude Code

---

## Summary

| Area | Status | Issues Found | Fixes Applied | Remaining Recommendations |
| --- | --- | --- | --- | --- |
| Authentication — Password Hashing | PASS | None | — | — |
| Authentication — Login Throttling | PASS | None | — | — |
| Authentication — Session Regeneration | PASS | None | — | — |
| Authentication — Session Invalidation on Logout | PASS | None | — | — |
| Authentication — Password Reset (OTP) | PASS | Weak reset password rule (min:8 only, no symbols/case) | Upgraded both admin and applicant reset to `Password::min(8)->mixedCase()->numbers()->symbols()` | — |
| Authorization — RBAC (Spatie Permission) | PASS | None | — | — |
| Authorization — Applicant blocked from admin panel | PASS | None | — | — |
| Authorization — Applicant cannot view another applicant's application | PASS | None | — | — |
| Authorization — Document download authorization | PASS | None | — | — |
| Authorization — Super Admin protection | PASS | None | — | — |
| Session Security — Config | PASS | None | — | — |
| Session Security — .env.example vars | PASS | None | — | — |
| XSS — Blade template output | PASS | None | — | Consider adding a CSP header at the web server |
| XSS — Unescaped `{!! !!}` usage | PASS | All uses are safe (HTMLPurifier, `e()`, or non-user-input) | — | — |
| File Upload — MIME / extension validation | PASS | SVG allowed for favicon upload | Removed `svg` from favicon allowed MIME list | — |
| File Upload — Max size enforcement | PASS | None | — | — |
| File Upload — SVG blocked for logos/sliders | PASS | None (already blocked in hero sliders and logo) | — | — |
| Hero Slider URL — javascript: blocked | PASS | None — `HttpOrHttpsUrl` rule already in place | — | — |
| CSRF — All POST routes | PASS | None | — | — |
| SQL Injection — Filter/sort parameters | PASS | None — all parameterized bindings | — | — |
| Data Protection — Private disk for documents | PASS | None — `local` disk used throughout | — | — |
| Database — Unique constraints | PASS | Key unique indexes present (email, phone, reference_number, applicant+vacancy) | — | — |
| Database — Performance indexes | PASS | Dedicated performance migration exists | — | — |
| Database — Raw SQL | PASS | None found | — | — |
| Dependency Security | INFO | Not checked at runtime | — | Run `composer audit` and `npm audit --omit=dev` before each deployment |
| Security Headers | INFO | Not set at application level | — | Add X-Frame-Options, X-Content-Type-Options, CSP at Nginx/Apache level |
| Tests — Security coverage | IMPROVED | Missing consolidated `SecurityTest.php` | Created `tests/Feature/SecurityTest.php` with 14 tests covering throttling, role separation, ownership, document download, and file validation | — |
| Documentation — SECURITY.md | IMPROVED | Existing file was minimal | Expanded to comprehensive security guide | — |
| Documentation — DEPLOYMENT.md | PASS | Already comprehensive | — | — |

---

## Detailed Findings

### FIXED: Favicon allows SVG uploads (Medium severity)

**Location:** `app/Http/Controllers/Admin/SettingsController.php`, `org.favicon` validation rule

**Before:**
```php
'org.favicon' => ['nullable', 'file', 'mimes:ico,png,jpg,jpeg,svg,webp', 'max:512'],
```

**After:**
```php
'org.favicon' => ['nullable', 'file', 'mimes:ico,png,jpg,jpeg,webp', 'max:512'],
```

**Impact:** SVG files can contain embedded `<script>` tags and event handlers. Allowing SVG as a favicon stored on the `public` disk and served directly by the web server creates a stored XSS vector if the browser renders the SVG inline (e.g. via `<img>` tags in some contexts, or via direct URL access). Removing SVG from allowed MIME types closes this path.

---

### FIXED: Password reset uses weak password rule (Low severity)

**Location:**
- `app/Http/Controllers/Admin/AdminPasswordResetController.php`
- `app/Http/Controllers/Auth/ApplicantPasswordResetController.php`

**Before:** `'password' => ['required', 'string', 'min:8', 'confirmed']`

**After:** `'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()]`

**Impact:** The registration form correctly enforced strong passwords, but the reset form allowed a weak replacement password (min 8 chars, no complexity). An attacker who triggered a reset (e.g. via phishing OTP delivery) could set a weaker password than registration allowed. This is now consistent across all password-setting flows.

---

### VERIFIED PASS: Application ownership (authorization)

`ApplicationPolicy::view()` correctly checks `$user->applicant?->id === $application->applicant_id` for non-admin users. The controller calls `$this->authorize('view', $application)` on the show method, and `$this->authorize('update', $application)` on edit/update.

---

### VERIFIED PASS: Document download authorization

Both `DocumentDownloadController` (applicant) and `AdminDocumentDownloadController` (admin) call `$this->authorize('view', $document)` before streaming. The `ApplicationDocumentPolicy::view()` checks applicant ownership for non-admin users, and admin permission for staff.

---

### VERIFIED PASS: Session security

`config/session.php` reads from environment variables with sensible defaults:
- `http_only` defaults to `true`
- `same_site` defaults to `'lax'`
- `secure` reads `SESSION_SECURE_COOKIE` (no hardcoded default — must be set in production `.env`)
- `.env.example` explicitly sets `SESSION_SECURE_COOKIE=true`

---

### VERIFIED PASS: OTP password reset security

- OTP stored as bcrypt hash — never plaintext
- 10-minute expiry enforced
- Previous OTPs deleted on new request
- Timing-safe `hash_equals()` for token comparison
- User enumeration prevented (success message regardless of whether email exists)

---

### VERIFIED PASS: Hero slider URL validation

`StoreHeroSliderRequest` and `UpdateHeroSliderRequest` both apply `['nullable', 'string', 'max:500', 'url', new HttpOrHttpsUrl]` to `button_link`. The `HttpOrHttpsUrl` rule explicitly rejects any scheme that is not `http` or `https`, blocking `javascript:`, `data:`, `ftp://`, and protocol-relative `//` URLs.

---

### VERIFIED PASS: SQL injection in filters

`AdminApplicationController::index()` uses:
```php
->where('first_name', 'like', "%$search%")
```
This is safe: Laravel's query builder passes the value as a PDO binding, not string-interpolated into the SQL query. The `like` clause value is parameterized even with `%` wildcards.

---

### VERIFIED PASS: Unescaped Blade output (`{!! !!}`)

All `{!! !!}` usages audited:

| Location | Value rendered | Safe? |
| --- | --- | --- |
| `public/home.blade.php` | `$ann->renderableHtml()` — HTMLPurifier-sanitized | Yes |
| `public/announcements/show.blade.php` | `$safeHtml` — HTMLPurifier-sanitized in controller | Yes |
| `admin/announcements/show.blade.php` | `$safeHtml` — HTMLPurifier-sanitized in controller | Yes |
| `public/vacancies/show.blade.php` | `nl2br(e($vacancy->description))` — `e()` escapes first | Yes |
| `admin/vacancies/show.blade.php` | `nl2br(e($vacancy->description))` — `e()` escapes first | Yes |
| `layouts/applicant.blade.php` | `$item['icon']` — SVG icon strings from controller array | Yes |
| `components/reg-field.blade.php` | `$label` — label HTML from component definition | Yes |
| `components/ethiopian-datepicker.blade.php` | `$label` — label HTML from component definition | Yes |

No direct user input is rendered unescaped.

---

## Recommendations (Not Yet Implemented)

1. **Add HTTP security headers at Nginx/Apache level** (X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Content-Security-Policy). These cannot be set from Laravel without an additional package.

2. **Run `composer audit` in CI/CD pipeline** to catch known vulnerable dependencies automatically.

3. **Consider `SESSION_ENCRYPT=true`** in production to encrypt session payloads at rest in Redis/database.

4. **Rate-limit the applicant registration endpoint** — currently at 5/min which is reasonable, but consider a CAPTCHA for large-scale public deployments.

5. **Implement a `Content-Security-Policy` header** to restrict inline scripts. This would require adding a nonce to all inline `<script>` blocks in Blade layouts.
