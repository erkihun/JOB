# Job Vacancy Announcement and Application System - Project Summary

## Stack

- Laravel 12
- Blade
- Tailwind CSS
- Alpine.js
- Spatie Permission
- Spatie Translatable
- Pest

The admin side is a Laravel Blade admin under `/admin`.

## Completed Areas

- Public vacancy listing and vacancy detail pages
- Search-first public job portal UI with responsive vacancy filters and cards
- Applicant registration, login, profile, dashboard, notifications
- Modern applicant application list and status tracking views
- Application submission and private document upload flow
- Screening workflow and reviewer assignment
- Exam and interview scheduling
- Notification templates and applicant notifications
- Reports center
- Audit logs
- English and Amharic localization
- Admin permission hardening
- Server-side announcement HTML sanitization
- SVG upload blocking for logo and hero image uploads
- HTTP/HTTPS-only hero slider links
- Production-only admin seeding from explicit environment credentials
- Noto Serif Ethiopic rollout for Amharic typography

## Main URLs

- Home: `/`
- Vacancies: `/vacancies`
- Track application: `/track`
- Applicant login: `/applicant/login`
- Applicant register: `/applicant/register`
- Applicant dashboard: `/applicant/dashboard`
- Admin login: `/admin/login`
- Admin dashboard: `/admin`

## Main Commands

```bash
composer install
npm install
php artisan migrate
php artisan db:seed
php artisan test
npm run build
php artisan serve
```

## UI Notes

- Tailwind CSS only for custom UI styling
- No external design-tool dependency or generated design artifact in maintained source
- Amharic uses `Noto Serif Ethiopic`
- Locale switching is handled by `/lang/{locale}` plus `SetLocale` middleware
- Shared UI components live under `resources/views/components`
- Public vacancy search uses `/vacancies?search=...` and preserves existing listing filters

## Documentation

- `ADMIN_UI_GUIDE.md`
- `APPLICANT_UI_GUIDE.md`
- `DEPLOYMENT.md`
- `SECURITY.md`

## Optional Improvements

- two-factor authentication for admins
- CAPTCHA on applicant auth flows
- SMS notifications
- automated backup package integration
- API layer for mobile or third-party integration
