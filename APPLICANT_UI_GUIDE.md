# Applicant UI Guide

## Overview

The public and applicant sides are Blade + Tailwind CSS + Alpine.js.

- Public pages live under `resources/views/public/*`
- Applicant pages live under `resources/views/applicant/*`
- Shared layouts:
  - `resources/views/layouts/public.blade.php`
  - `resources/views/layouts/applicant.blade.php`

Custom styling is Tailwind-only.

## Modern UI Components

Reusable Blade components keep the public and applicant UI consistent:

- `resources/views/components/page-header.blade.php`
- `resources/views/components/stat-card.blade.php`
- `resources/views/components/status-badge.blade.php`
- `resources/views/components/empty-state.blade.php`
- `resources/views/components/public/vacancy-search.blade.php`

Use these before adding one-off cards, badges, empty states, or search panels.

## Public Job Portal Pattern

The public home page is search-first:

- hero vacancy search posts to `/vacancies`
- open vacancy cards use the same blue/orange/green/slate brand system
- vacancy listing keeps filters responsive and paginated
- vacancy detail keeps the apply CTA visible without changing deadline or duplicate-application rules

## Routes

Public:

- `/`
- `/vacancies`
- `/vacancies/{vacancy}`
- `/track`
- `/applicant/login`
- `/applicant/register`

Applicant:

- `/applicant/dashboard`
- `/applicant/profile`
- `/applicant/applications`
- `/applicant/notifications`

## Localization

Locale switching uses:

- `GET /lang/{locale}`

Locale priority:

1. authenticated user locale
2. authenticated applicant locale
3. session locale
4. `localization.default_locale`
5. English fallback

Layouts apply:

- `lang-en` / `lang-am`
- `locale-en` / `locale-am`

## Amharic Font

Amharic now uses `Noto Serif Ethiopic` from `@fontsource/noto-serif-ethiopic`.

The font is loaded in:

- `resources/css/app.css`

It applies to:

- public pages
- applicant pages
- admin pages
- forms
- tables
- notifications

## Security

- applicant routes require `auth` + `applicant`
- admin users are blocked from applicant routes
- application and profile documents are stored privately on the `local` disk
- downloads go through authorization-aware controllers
- applicants can only access their own profile, applications, and documents

## Main Commands

```bash
php artisan test tests/Feature/Applicant/
php artisan test tests/Feature/UiLoadTest.php
php artisan test
npm run build
php artisan serve
```
