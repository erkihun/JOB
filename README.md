# Job Vacancy Announcement and Application System

## Stack

- Laravel 12
- Blade
- Tailwind CSS
- Alpine.js
- Pest

## UI Notes

- Custom UI styling is implemented with Tailwind CSS only.
- The admin side is a Blade admin under `/admin`.
- Public and applicant portals also use Blade + Tailwind + Alpine.
- No external design-tool dependency or imported design artifact is part of the maintained source.
- Amharic typography uses `Noto Serif Ethiopic`.

## Main Routes

- Public home: `/`
- Public vacancies: `/vacancies`
- Applicant login: `/applicant/login`
- Applicant register: `/applicant/register`
- Applicant dashboard: `/applicant/dashboard`
- Admin login: `/admin/login`
- Admin dashboard: `/admin`

## Development

```bash
composer install
npm install
php artisan migrate
php artisan db:seed
php artisan serve
npm run dev
```

## Verification

```bash
vendor\bin\pint.bat
php artisan test
npm run build
```

## Documentation

All project documentation lives in the [`docs/`](docs/) directory:

- [`docs/PROJECT_SUMMARY.md`](docs/PROJECT_SUMMARY.md) — system overview
- [`docs/ADMIN_UI_GUIDE.md`](docs/ADMIN_UI_GUIDE.md) — admin interface guide
- [`docs/APPLICANT_UI_GUIDE.md`](docs/APPLICANT_UI_GUIDE.md) — applicant interface guide
- [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md) — production deployment & hardening
- [`docs/SECURITY.md`](docs/SECURITY.md) — security policy & controls
- [`docs/PENTEST_FINDINGS_AND_SOLUTIONS.md`](docs/PENTEST_FINDINGS_AND_SOLUTIONS.md) — penetration test report
- [`docs/SECURITY_FIX_CHECKLIST.md`](docs/SECURITY_FIX_CHECKLIST.md) — security checklist
- [`docs/TODO.md`](docs/TODO.md) — roadmap
