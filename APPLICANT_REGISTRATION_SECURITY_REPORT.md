# Applicant Registration — Security & Functionality Report

**System:** Laravel 12 Job Vacancy / Recruitment Portal
**Scope:** Applicant self-registration (`/applicant/register`) — end-to-end security, functionality, localization, and test coverage.
**Date:** 2026-06-09
**Auth model:** Single `users` table shared by admins and applicants, separated by Spatie roles.

---

## 1. Registration flow summary

```
GET  /applicant/register   (guest only, throttle implicit via web)
  → ApplicantAuthController@showRegister
  → aborts 403 if recruitment.allow_registration = false
  → renders 6-step Alpine.js wizard (resources/views/applicant/auth/register.blade.php)

POST /applicant/register   (guest only, throttle:5,1)
  → ApplicantRegisterRequest  (validation + normalization + dangerous-upload guard)
  → ApplicantAuthController@register
      → RegisterApplicantAction@handle  (DB::transaction)
          → User::create(...)        explicit array, status hardcoded 'active'
          → $user->assignRole('applicant')
          → Applicant::create(...)   explicit array
          → stores document on PRIVATE local disk, UUID filename
      → Auth::login($user)
      → sends 6-digit email-verification OTP
  → redirect to applicant.verify-email
```

**Post-registration gate:** `EnsureIsApplicant` middleware blocks `/applicant/*` until the
account is active **and** `email_verified_at` is set. `AdminAuthenticate` blocks `/admin/*`
for any user without a non-applicant role.

---

## 2. Security checks performed

| Area | Check | Result |
|------|-------|--------|
| Mass assignment | Action builds explicit arrays; `$request->validated()` only | ✅ Safe |
| Role injection | `role` / `roles` / `permission` / `is_admin` / `user_type` in payload ignored | ✅ Safe |
| Status injection | `status` hardcoded to `active`, request value stripped | ✅ Safe |
| Privilege escalation | `created_by`, `email_verified_at` cannot be set from request | ✅ Safe |
| Admin boundary | New applicant bounced from `admin.dashboard`; `canAccessAdminArea()` false | ✅ Safe |
| Password policy | Applicant policy from System Settings (separate from admin) enforced | ✅ Safe |
| Password confirmation | `confirmed` rule enforced | ✅ Safe |
| Throttling | `throttle:5,1` on POST register; `throttle:login` on login | ✅ Safe |
| CSRF | POST route inside `web` group → `VerifyCsrfToken` | ✅ Safe |
| Uniqueness | email/phone/national_id unique at **DB level + Form Request** (defense in depth) | ✅ Safe |
| XSS | Plain-text fields, Blade `{{ }}` auto-escape; no `{!!` in applicant views | ✅ Safe |
| SQL injection | Eloquent parameter binding; literal storage verified | ✅ Safe |
| File size | `max:` from `recruitment.max_file_size_mb` (default 2 MB) | ✅ Safe |
| File type | `mimes:` allow-list **+ hard SVG/HTML/XML/JS/PHP denylist** | ✅ Fixed |
| Private storage | Documents on `local` disk, never `public`; UUID filenames | ✅ Safe |
| Document access | Admin preview gated by `authorize('view', $document)` | ✅ Safe |
| Info leakage | Validation errors localized, no DB/stack details exposed | ✅ Safe |
| Profile photo | `prohibited` during registration (deferred to profile edit) | ✅ Safe |

---

## 3. Issues found

### Issue 1 — SVG/script-capable uploads relied solely on a configurable allow-list (Medium)
`documents` was validated with `mimes:{allowed_file_types}` where `allowed_file_types`
is an **admin-editable setting**. If an administrator added `svg` (or `html`, `xml`) to
that list, a script-bearing file could be stored and later served, enabling stored XSS.

**Severity:** Medium (requires a misconfiguration, but the blast radius is stored XSS).

### Issue 2 — Defense-in-depth confirmation (Informational)
Mass-assignment, role/status injection, and private storage were already implemented
correctly. No code change required — locked in with regression tests so future refactors
cannot silently weaken them.

---

## 4. Fixes applied

### Fix for Issue 1 — hard denylist independent of settings
`app/Http/Requests/Auth/ApplicantRegisterRequest.php`

- Added a closure validation rule `rejectDangerousUpload()` on the `documents` field that
  **always** rejects script-capable uploads regardless of `allowed_file_types`:
  - Blocked extensions: `svg, svgz, html, htm, xml, xhtml, js, php, phtml, phar`
  - Blocked MIME types: `image/svg+xml, text/html, application/xml, text/xml,
    application/xhtml+xml, application/javascript, text/javascript, application/x-php, text/x-php`
- Checks **both** the client extension and the guessed MIME type, so a renamed file
  (e.g. `payload.svg` → `payload.pdf` with SVG content) is still caught by MIME.
- Error message reuses the localized `validation.mimes` key (en + am covered).

No changes were made to the controller, action, models, routes, or migrations — the
existing implementation was otherwise sound.

---

## 5. Tests added

New file: `tests/Feature/Security/ApplicantRegistrationSecurityTest.php` (16 tests)

| Test | Verifies |
|------|----------|
| assigns only the applicant role | role injection ignored, exactly `['applicant']` |
| cannot set user status from request | status forced to `active` |
| ignores injected created_by / email_verified_at | no privilege/verification bypass |
| newly registered applicant cannot access admin | admin boundary + `canAccessAdminArea()` |
| weak password rejected | password policy, no account created |
| password confirmation required | `confirmed` rule |
| svg document rejected | hard denylist (SVG masquerade) |
| html document rejected | hard denylist |
| oversized document rejected | `max:` size rule |
| profile photo cannot be set | `prohibited` rule |
| document stored privately + randomized name | private disk, UUID name, original preserved |
| xss payload stored verbatim & escaped on output | Blade auto-escaping |
| sql injection treated as literal | Eloquent binding, table intact |
| registration rate limited | `throttle:5,1` returns 429 |
| route protected by csrf in web group | middleware assertion |
| amharic validation message when locale am | localized error contains Ethiopic script |

Existing suites still cover: page load, successful registration, user+profile creation,
role assignment, duplicate email/phone/national_id, name persistence, preferred_locale,
disability_type conditional, private document storage, locale-split date input,
review-step pre-population.

---

## 6. Remaining recommendations

1. **Antivirus scan on uploads (defense in depth).** For production, scan stored
   documents (e.g. ClamAV) before they are served to admins. Out of scope here.
2. **Re-validate `allowed_file_types` on the settings form** to warn admins if they add a
   risky type — the denylist now neutralizes it, but a UI warning improves clarity.
3. **Consider a dedicated `email_verified` enforcement on sensitive applicant actions**
   (currently enforced at the dashboard gate, which is sufficient for now).
4. **CSRF runtime negative test** was intentionally omitted (the test harness disables the
   middleware globally); the structural assertion that the route is in the `web` group is the
   reliable proof. If desired, add a browser-level (Dusk) test for a true 419.

---

## 7. Production readiness — Applicant Registration

**Status: READY**

- All functional requirements implemented and tested.
- All in-scope security checks pass; the one real gap (configurable SVG allow) is fixed
  with a settings-independent hard denylist.
- Localization (en/am) including Amharic validation messages and Noto Serif Ethiopic
  typography is in place.
- Admin/applicant separation on the unified users table is enforced and tested.
- Full test suite green; front-end build succeeds; Pint clean.
