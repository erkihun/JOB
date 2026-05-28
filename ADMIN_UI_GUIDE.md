# Admin UI Guide

## Architecture

The admin side is a classic Laravel 12 Blade admin under `/admin`.

Primary entrypoints:

- `routes/web.php`
- `app/Http/Middleware/AdminAuthenticate.php`
- `app/Http/Middleware/SetLocale.php`
- `app/Http/Controllers/Admin/*`
- `resources/views/layouts/admin.blade.php`
- `resources/views/admin/*`
- `resources/css/app.css`
- `lang/en/*`
- `lang/am/*`

The admin panel in this repository is controller-and-Blade driven.

## Styling

- Tailwind CSS only for custom UI styling
- Alpine.js for small layout interactions
- No external design-tool dependency or generated design artifact
- No Bootstrap or external dashboard kit
- Admin page transitions use the Blade admin shell and progressively swap the content frame for safe internal links, preserving normal full navigation for downloads, previews, exports, logout, and script-heavy workflows

Shared frontend entry:

- `resources/css/app.css`
- `resources/js/app.js`

## Color System

- Primary: `blue-600`
- Primary hover: `blue-700`
- Accent / warning: `orange-500`
- Accent hover: `orange-600`
- Success: `green-600`
- Success hover: `green-700`
- Danger: `red-600`
- Danger hover: `red-700`
- Neutral surfaces/text: `slate` and `gray`

## Admin Shell

Layout file:

- `resources/views/layouts/admin.blade.php`

Shell responsibilities:

- fixed sidebar navigation
- responsive topbar
- locale switcher
- authenticated user menu
- flash/error messages
- full-width content area

Locale classes are applied on both `<html>` and `<body>`:

- `lang-en` / `lang-am`
- `locale-en` / `locale-am`

## Navigation

The sidebar is permission-aware and grouped inline in the layout.

Current groups:

1. Dashboard
2. Recruitment
3. Exams & Interviews
4. Notifications
5. Reports
6. Access Control
7. System

Visibility is driven by `hasPermissionTo()` checks in the layout and enforced again at the route level.

## Route Protection

Admin routes are registered in `routes/web.php` inside:

- `auth`
- `admin`

Then protected per page/action with permission middleware such as:

- `permission:dashboard.view`
- `permission:users.view`
- `permission:vacancies.view`
- `permission:applications.view`
- `permission:screening.view`
- `permission:reports.view`
- `permission:settings.view`
- `permission:audit.view`

This means hidden menus are not the only protection. Unauthorized requests are denied server-side.

## Main Admin Pages

- Dashboard: `AdminDashboardController`
- Users: `UserController`
- Roles: `RoleController`
- Vacancies: `VacancyController`
- Applications: `ApplicationController`
- Screening: `ScreeningController`
- Schedules: `ScheduleController`
- Notification Templates: `NotificationTemplateController`
- Reports: `ReportsController`
- Settings: `SettingsController`
- Audit Logs: `AuditLogController`

## Localization

Locale resolution order in `SetLocale`:

1. authenticated user `preferred_locale`
2. authenticated applicant `preferred_locale`
3. session locale
4. `localization.default_locale` setting
5. English fallback

Language switcher route:

- `GET /lang/{locale}`

Behavior:

- stores locale in session
- updates authenticated `users.preferred_locale`
- updates related applicant `preferred_locale` when present
- redirects back

## Amharic Typography

Amharic uses `Noto Serif Ethiopic`.

Source:

- `@fontsource/noto-serif-ethiopic`

Loaded from:

- `resources/css/app.css`

Applied through:

- `html[lang='am']`
- `.lang-am`
- `.locale-am`

English stays on the default sans stack.

## Sensitive Data Rules

Sensitive applicant fields must not be exposed without `applications.view-sensitive`.

Current guarded surfaces:

- dashboard recent applications
- admin applications list
- admin application detail
- screening list
- screening review

When permission is missing, the UI renders `__('dashboard.restricted')` instead of the sensitive value.

## Adding a New Admin Page

1. Add a controller under `app/Http/Controllers/Admin`.
2. Register the route in `routes/web.php`.
3. Add route-level permission middleware.
4. Create the Blade view under `resources/views/admin/...`.
5. Use `layouts.admin`.
6. Add translation keys instead of hard-coded visible text.
7. Add or update a feature test under `tests/Feature/Admin`.

Useful make commands:

```bash
php artisan make:controller Admin/ExampleController
php artisan make:test ExampleAdminPageTest
```

## Testing the Admin Side

Primary commands:

```bash
php artisan route:list --path=admin
php artisan test tests/Feature/Admin/
php artisan test tests/Feature/UiLoadTest.php
php artisan test
npm run build
```

Focused checks used in this project:

- dashboard access and permission tests
- applicant blocked from admin
- route list contains expected admin routes
- sensitive data masking checks
- locale switch persistence checks
- no design-tool reference regression check

## Customizing Branding

Organization branding is pulled from settings:

- `org.name`
- `org.logo`

Update those via `/admin/settings`.

If more branding fields are added later, keep the lookup centralized in controllers/views rather than scattering raw `Setting::get()` calls everywhere.
