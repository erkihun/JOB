# Cybersecurity Solutions Checklist

Practical, prioritized controls for this Laravel 12 recruitment system. Legend:
**[✅ Done]** implemented & tested in code · **[⚙️ Config]** set via env/server · **[📋 Ops]** hosting/operational task.

---

## Authentication hardening
- [✅ Done] Configurable password policy (length, upper/lower/number/symbol, common-password block) — `PasswordPolicyService`, `PasswordPolicyRule`
- [✅ Done] Login throttling (3–20/min, email+IP keyed) — `AppServiceProvider`
- [✅ Done] Registration, apply, upload, password-reset, OTP throttling — route `throttle:` middleware
- [✅ Done] Session regeneration on login (anti-fixation); invalidation on logout
- [⚙️ Config] Enforce password expiry/history once age storage is enabled (fields exist, not yet enforced)

## MFA for admins
- [✅ Done] TOTP MFA + recovery codes + remembered devices — `MfaController`
- [✅ Done] Per-role MFA enforcement + `RequireTwoFactorSetup` middleware
- [📋 Ops] System owner sign-off: require MFA for all admin roles in production settings

## RBAC / permission review
- [✅ Done] Spatie roles/permissions; `Gate::before` super-admin, `Gate::after` applicant lockdown
- [✅ Done] Policies: User, Vacancy, Application, ApplicationDocument, ApplicantProfileDocument, Institution, Setting, AuditLog
- [✅ Done] Last-active-super-admin delete/deactivate protection — model hooks
- [📋 Ops] Quarterly access review of admin accounts and role assignments

## Session timeout
- [✅ Done] `EnforceSessionTimeout` middleware
- [⚙️ Config] `SESSION_LIFETIME` (default 120 min) — tune per policy

## HTTPS / TLS
- [✅ Done] `URL::forceScheme('https')` in production; HSTS header (prod+HTTPS)
- [⚙️ Config] `SESSION_SECURE_COOKIE=true` (in `.env.example`)
- [📋 Ops] Provision TLS cert; force HTTP→HTTPS redirect at proxy/web server; document in `docs/DEPLOYMENT.md`

## Secure cookies
- [⚙️ Config] `SESSION_SECURE_COOKIE=true`, `SESSION_HTTP_ONLY=true`, `SESSION_SAME_SITE=lax`, `SESSION_ENCRYPT=true`

## Private file storage
- [✅ Done] All uploads on private `local` disk; never `public`
- [✅ Done] Authorized streaming/download controllers with ownership policies
- [✅ Done] Randomized (UUID) stored filenames; original name preserved separately

## Input validation
- [✅ Done] Form Requests on all state-changing endpoints
- [✅ Done] Explicit-array model creation (no mass assignment of role/status)
- [✅ Done] Enum/Rule::in constraints on status, gender, locale, education level

## XSS prevention
- [✅ Done] Blade auto-escaping; HTMLPurifier for rich content (`SanitizeHtml`)
- [✅ Done] `{!!` limited to sanitized, `e()`-escaped, or static internal values

## SQL injection prevention
- [✅ Done] Eloquent/Query Builder with bound parameters; `whereRaw(..., [bindings])`
- [✅ Done] No request-driven raw `orderBy`/`selectRaw` with user input

## CSRF protection
- [✅ Done] `VerifyCsrfToken` on `web` group; no routes in except list (tested)
- [✅ Done] Graceful 419/expired-session redirect

## Rate limiting
- [✅ Done] login / register / apply / upload / password-reset / OTP / MFA endpoints
- [⚙️ Config] `security.login_attempts` setting (3–20)
- [📋 Ops] Edge rate limiting (WAF) for volumetric abuse

## Upload security
- [✅ Done] `SafeUploadRule` rejects SVG/HTML/XML/JS/PHP on **all** upload paths
- [✅ Done] `max:2MB` + `mimes:pdf,jpg,jpeg,png` allow-list
- [📋 Ops] Server-side antivirus scan (e.g. ClamAV) before serving documents

## Audit logging
- [✅ Done] Settings change, user CRUD, screening decision, MFA, **sensitive PII access**
- [📋 Ops] Ship audit logs to a central, tamper-evident store; retention policy

## Backups
- [📋 Ops] Automated daily encrypted DB + storage backups; offsite copy
- [📋 Ops] Periodic restore drills

## Disaster recovery
- [📋 Ops] Documented RTO/RPO; runbook; tested failover/restore

## Monitoring and alerting
- [📋 Ops] Error/uptime monitoring; alert on auth failures, 5xx spikes, failed queue jobs
- [📋 Ops] CSP report endpoint (`CSP_REPORT_URI`) to catch policy violations

## Vulnerability scanning
- [✅ Done] `composer audit` clean
- [📋 Ops] `npm audit fix` (dev `shell-quote`); schedule recurring SCA/DAST in CI

## Patch management
- [📋 Ops] Track Laravel/PHP/dependency releases; monthly patch window

## Server hardening
- [📋 Ops] Least-privilege OS user; disable directory listing; restrict `storage/` web access; PHP `expose_php=Off`; `APP_DEBUG=false`

## DDoS protection
- [📋 Ops] CDN/WAF (Cloudflare or equivalent); connection/rate limits at edge

## Load testing
- [📋 Ops] k6/JMeter plan for apply + dashboard endpoints; validate pagination & queue throughput before launch

## Incident response
- [📋 Ops] IR runbook: detection, containment, eradication, recovery, comms; named on-call owner
