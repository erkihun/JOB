<?php

use App\Http\Controllers\Admin\AdminApplicantController;
use App\Http\Controllers\Admin\AdminApplicantProfileDocumentDownloadController;
use App\Http\Controllers\Admin\AdminApplicantProfileDocumentPreviewController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminDocumentDownloadController;
use App\Http\Controllers\Admin\AdminPasswordResetController;
use App\Http\Controllers\Admin\AdminProfileController;
use App\Http\Controllers\Admin\ApplicationController as AdminApplicationController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\ExamInterviewResultController;
use App\Http\Controllers\Admin\FinalResultAnnouncementController;
use App\Http\Controllers\Admin\FinalResultController;
use App\Http\Controllers\Admin\HeroSliderController;
use App\Http\Controllers\Admin\InstitutionController as AdminInstitutionController;
use App\Http\Controllers\Admin\NotificationTemplateController;
use App\Http\Controllers\Admin\ReportsController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\ScreeningController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VacancyAnnouncementController;
use App\Http\Controllers\Admin\VacancyController as AdminVacancyController;
use App\Http\Controllers\Applicant\ApplicantDashboardController;
use App\Http\Controllers\Applicant\ApplicantNotificationController;
use App\Http\Controllers\Applicant\ApplicantProfileController;
use App\Http\Controllers\Applicant\ApplicantProfileDocumentController;
use App\Http\Controllers\Applicant\ApplicationController;
use App\Http\Controllers\Applicant\DocumentDownloadController;
use App\Http\Controllers\Applicant\ProfilePhotoController;
use App\Http\Controllers\Applicant\VacancyController as ApplicantVacancyController;
use App\Http\Controllers\Auth\ApplicantAuthController;
use App\Http\Controllers\Auth\ApplicantEmailVerificationController;
use App\Http\Controllers\Auth\ApplicantPasswordResetController;
use App\Http\Controllers\Auth\MfaController;
use App\Http\Controllers\Auth\UnifiedAuthController;
use App\Http\Controllers\Public\AnnouncementController as PublicAnnouncementController;
use App\Http\Controllers\Public\ApplicationTrackingController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\VacancyController;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

// Language Switcher
Route::get('/lang/{locale}', function (string $locale) {
    $configured = Setting::get('app.available_locales', config('app.available_locales', ['en', 'am']));
    if (is_string($configured)) {
        $configured = array_filter(explode(',', $configured));
    }
    $available = array_values((array) $configured);

    if (in_array($locale, $available, true)) {
        Session::put('locale', $locale);
        /** @var User|null $user */
        $user = auth()->user();
        if ($user !== null) {
            $user->update(['preferred_locale' => $locale]);
            $user->applicant?->update(['preferred_locale' => $locale]);
        }
    }

    return redirect()->back();
})->name('lang.switch')->where('locale', '[a-z]{2}');

// Default login redirect alias — auth middleware uses 'login' route name
Route::get('/login', [UnifiedAuthController::class, 'show'])->name('login');
Route::middleware('guest')->group(function (): void {
    Route::post('/login', [UnifiedAuthController::class, 'login'])->middleware('throttle:login');
});
Route::post('/logout', [UnifiedAuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// Public routes (all behind SetLocale middleware defined in bootstrap/app.php)
Route::get('/', HomeController::class)->name('home');
Route::get('/vacancies', [VacancyController::class, 'index'])->name('vacancies.index');
Route::get('/vacancies/{vacancy}', [VacancyController::class, 'show'])->name('vacancies.show');
Route::get('/announcements', [PublicAnnouncementController::class, 'index'])->name('announcements.index');
Route::get('/announcements/{announcement}', [PublicAnnouncementController::class, 'show'])->name('announcements.show');

// Public application tracking (no login required)
Route::get('/track', [ApplicationTrackingController::class, 'show'])->name('track.show');
Route::post('/track', [ApplicationTrackingController::class, 'search'])->middleware('throttle:10,1')->name('track.search');

// Applicant Auth (guests only)
Route::middleware('guest')->prefix('applicant')->name('applicant.')->group(function () {
    Route::get('/register', [ApplicantAuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [ApplicantAuthController::class, 'register'])->middleware('throttle:5,1');

    Route::get('/login', fn () => redirect()->route('login'))->name('login');
    Route::post('/login', [UnifiedAuthController::class, 'login'])->middleware('throttle:login');

    // Registration helpers
    Route::get('/temp-photo', [ApplicantAuthController::class, 'tempPhoto'])->name('temp-photo');
    Route::get('/validate-field', [ApplicantAuthController::class, 'validateField'])->name('validate-field');

    // Password reset via OTP
    Route::get('/forgot-password', [ApplicantPasswordResetController::class, 'showForgotForm'])->name('password.request');
    Route::post('/forgot-password', [ApplicantPasswordResetController::class, 'sendOtp'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/verify-otp', [ApplicantPasswordResetController::class, 'showOtpForm'])->name('password.otp');
    Route::post('/verify-otp', [ApplicantPasswordResetController::class, 'verifyOtp'])->middleware('throttle:10,1')->name('password.verify-otp');
    Route::get('/reset-password', [ApplicantPasswordResetController::class, 'showResetForm'])->name('password.reset.show');
    Route::post('/reset-password', [ApplicantPasswordResetController::class, 'resetPassword'])->middleware('throttle:5,1')->name('password.reset');
});

// Applicant logout (auth only — no applicant role check needed)
Route::post('/applicant/logout', [UnifiedAuthController::class, 'logout'])
    ->middleware('auth')
    ->name('applicant.logout');

// Email verification (auth required, but no email_verified_at check — before EnsureIsApplicant)
Route::middleware(['auth', 'session.timeout'])->prefix('applicant')->name('applicant.')->group(function () {
    Route::get('/verify-email', [ApplicantEmailVerificationController::class, 'show'])->name('verify-email');
    Route::post('/verify-email', [ApplicantEmailVerificationController::class, 'verify'])->middleware('throttle:10,1')->name('verify-email.submit');
    Route::post('/verify-email/resend', [ApplicantEmailVerificationController::class, 'resend'])->middleware('throttle:3,1')->name('verify-email.resend');
});

// ── Admin panel routes ────────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->group(function () {

    // Guest-only auth
    Route::middleware('guest')->group(function () {
        Route::get('/login', fn () => redirect()->route('login'))->name('login');
        Route::post('/login', [UnifiedAuthController::class, 'login'])->middleware('throttle:login');

        // Password reset via OTP
        Route::get('/forgot-password', [AdminPasswordResetController::class, 'showForgotForm'])->name('password.request');
        Route::post('/forgot-password', [AdminPasswordResetController::class, 'sendOtp'])->middleware('throttle:5,1')->name('password.email');
        Route::get('/verify-otp', [AdminPasswordResetController::class, 'showOtpForm'])->name('password.otp');
        Route::post('/verify-otp', [AdminPasswordResetController::class, 'verifyOtp'])->middleware('throttle:10,1')->name('password.verify-otp');
        Route::get('/reset-password', [AdminPasswordResetController::class, 'showResetForm'])->name('password.reset.show');
        Route::post('/reset-password', [AdminPasswordResetController::class, 'resetPassword'])->middleware('throttle:5,1')->name('password.reset');
    });

    Route::post('/logout', [UnifiedAuthController::class, 'logout'])->middleware('auth')->name('logout');

    // Login-step 2FA challenge (user is authenticated but 2FA session not yet verified)
    Route::middleware(['auth', 'session.timeout'])->group(function () {
        Route::get('/login/two-factor', [MfaController::class, 'challenge'])
            ->name('login.two-factor');
        Route::post('/login/two-factor', [MfaController::class, 'verify'])
            ->middleware('throttle:10,1');
    });

    // All other admin routes require authentication + 2FA enforcement
    Route::middleware(['admin', 'session.timeout', 'require2fa'])->group(function () {
        Route::get('/profile', [AdminProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [AdminProfileController::class, 'update'])->name('profile.update');

        // Two-factor authentication setup / management
        Route::get('/two-factor', [MfaController::class, 'show'])->name('two-factor.show');
        Route::post('/two-factor/enable', [MfaController::class, 'enable'])->middleware('throttle:10,1')->name('two-factor.enable');
        Route::post('/two-factor/disable', [MfaController::class, 'disable'])->middleware('throttle:10,1')->name('two-factor.disable');

        Route::get('/', AdminDashboardController::class)
            ->middleware('permission:dashboard.view')
            ->name('home');
        Route::get('/dashboard', AdminDashboardController::class)
            ->middleware('permission:dashboard.view')
            ->name('dashboard');

        // Institution management
        Route::resource('institutions', AdminInstitutionController::class)
            ->middleware('permission:institutions.view')
            ->middlewareFor(['create', 'store'], 'permission:institutions.create')
            ->middlewareFor(['edit', 'update'], 'permission:institutions.update')
            ->middlewareFor('destroy', 'permission:institutions.delete');
        Route::post('/institutions/{institution}/activate', [AdminInstitutionController::class, 'activate'])
            ->middleware('permission:institutions.activate')
            ->name('institutions.activate');
        Route::post('/institutions/{institution}/deactivate', [AdminInstitutionController::class, 'deactivate'])
            ->middleware('permission:institutions.deactivate')
            ->name('institutions.deactivate');

        Route::resource('vacancies', AdminVacancyController::class)
            ->middleware('permission:vacancies.view')
            ->middlewareFor(['create', 'store'], 'permission:vacancies.create')
            ->middlewareFor(['edit', 'update'], 'permission:vacancies.update')
            ->middlewareFor('destroy', 'permission:vacancies.delete');

        Route::resource('announcements', VacancyAnnouncementController::class)
            ->middleware('permission:vacancies.view')
            ->middlewareFor(['create', 'store'], 'permission:vacancies.create')
            ->middlewareFor(['edit', 'update'], 'permission:vacancies.update')
            ->middlewareFor('destroy', 'permission:vacancies.delete');

        Route::resource('hero-sliders', HeroSliderController::class)
            ->except(['show'])
            ->middleware('permission:settings.view')
            ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], 'permission:settings.view');

        Route::resource('applications', AdminApplicationController::class)
            ->only(['index', 'show'])
            ->middleware('permission:applications.view');

        Route::resource('applicants', AdminApplicantController::class)
            ->only(['index', 'show'])
            ->middleware('permission:applications.view');

        Route::get('/applicants/{applicant}/photo', [AdminApplicantController::class, 'photo'])
            ->middleware('permission:applications.view')
            ->name('applicants.photo');

        Route::resource('users', UserController::class)
            ->except(['show'])
            ->middleware('permission:users.view')
            ->middlewareFor(['create', 'store'], 'permission:users.create')
            ->middlewareFor(['edit', 'update'], 'permission:users.update')
            ->middlewareFor('destroy', 'permission:users.delete');

        Route::resource('roles', RoleController::class)
            ->only(['index', 'edit', 'update'])
            ->middleware('permission:roles.view')
            ->middlewareFor('update', 'permission:permissions.manage');

        Route::resource('schedules', ScheduleController::class)
            ->except(['show'])
            ->middleware('role_or_permission:super_admin|admin|hr_manager|hr_officer|exam_officer|interview_officer')
            ->middlewareFor('index', 'role_or_permission:super_admin|admin|hr_manager|hr_officer|exam_officer|interview_officer')
            ->middlewareFor(['create', 'store'], 'role_or_permission:super_admin|admin|hr_manager|exam_officer|interview_officer')
            ->middlewareFor(['edit', 'update'], 'role_or_permission:super_admin|admin|hr_manager|exam_officer|interview_officer')
            ->middlewareFor('destroy', 'role_or_permission:super_admin|admin|hr_manager|exam_officer|interview_officer');

        Route::get('/schedule-results', [ExamInterviewResultController::class, 'schedules'])
            ->name('schedule-results.index')
            ->middleware('role_or_permission:super_admin|admin|hr_manager|exam_officer|interview_officer|exams.record-results|interviews.record-results');

        Route::get('/schedules/{schedule}/results', [ExamInterviewResultController::class, 'index'])
            ->name('schedules.results')
            ->middleware('role_or_permission:super_admin|admin|hr_manager|exam_officer|interview_officer|exams.record-results|interviews.record-results');

        Route::post('/schedules/{schedule}/applicants/assign', [ExamInterviewResultController::class, 'assignApplicants'])
            ->name('schedules.applicants.assign')
            ->middleware('role_or_permission:super_admin|admin|hr_manager|exam_officer|interview_officer|exams.assign-applicants|interviews.assign-applicants');

        Route::post('/schedules/{schedule}/applicants/{applicantRecord}/result', [ExamInterviewResultController::class, 'store'])
            ->name('schedules.results.store')
            ->middleware('role_or_permission:super_admin|admin|hr_manager|exam_officer|interview_officer|exams.record-results|interviews.record-results');

        Route::get('/final-results', [FinalResultController::class, 'index'])
            ->name('final-results.index')
            ->middleware('role_or_permission:super_admin|admin|hr_manager|hr_officer|exam_officer|interview_officer');

        Route::get('/final-results/{application}/create', [FinalResultController::class, 'create'])
            ->name('final-results.create')
            ->middleware('role_or_permission:super_admin|admin|hr_manager|exam_officer|interview_officer');

        Route::post('/final-results/announce', [FinalResultAnnouncementController::class, 'store'])
            ->name('final-results.announce')
            ->middleware('role_or_permission:super_admin|admin|hr_manager|exam_officer|interview_officer');

        Route::post('/final-results/{application}', [FinalResultController::class, 'store'])
            ->name('final-results.store')
            ->middleware('role_or_permission:super_admin|admin|hr_manager|exam_officer|interview_officer');

        Route::get('/final-results/{application}/edit', [FinalResultController::class, 'edit'])
            ->name('final-results.edit')
            ->middleware('role_or_permission:super_admin|admin|hr_manager|exam_officer|interview_officer');

        Route::put('/final-results/{application}', [FinalResultController::class, 'update'])
            ->name('final-results.update')
            ->middleware('role_or_permission:super_admin|admin|hr_manager|exam_officer|interview_officer');

        Route::resource('notification-templates', NotificationTemplateController::class)
            ->only(['index', 'edit', 'update'])
            ->middleware('permission:notifications.templates.manage');

        Route::resource('audit-logs', AuditLogController::class)
            ->only(['index'])
            ->middleware('permission:audit.view');

        Route::get('/screening', [ScreeningController::class, 'index'])
            ->middleware('permission:screening.view')
            ->name('screening.index');
        Route::get('/screening/passed', [ScreeningController::class, 'passed'])
            ->middleware('permission:screening.view')
            ->name('screening.passed');
        Route::get('/screening/passed/export', [ScreeningController::class, 'exportPassed'])
            ->middleware('permission:screening.view')
            ->name('screening.passed.export');
        Route::get('/screening/failed', [ScreeningController::class, 'failed'])
            ->middleware('permission:screening.view')
            ->name('screening.failed');
        Route::get('/screening/failed/export', [ScreeningController::class, 'exportFailed'])
            ->middleware('permission:screening.view')
            ->name('screening.failed.export');
        Route::get('/screening/{application}', [ScreeningController::class, 'review'])
            ->middleware('permission:screening.view')
            ->name('screening.review');
        Route::post('/screening/{application}', [ScreeningController::class, 'submitReview'])
            ->middleware('permission:screening.review')
            ->name('screening.submit');

        Route::get('/settings', [SettingsController::class, 'index'])
            ->middleware('permission:settings.view')
            ->name('settings.index');
        Route::put('/settings', [SettingsController::class, 'update'])
            ->middleware(['permission:settings.manage', 'permission:settings.security'])
            ->name('settings.update');

        Route::get('/reports', [ReportsController::class, 'index'])
            ->middleware('permission:reports.view')
            ->name('reports.index');
        Route::get('/reports-center', [ReportsController::class, 'index'])
            ->middleware('permission:reports.view')
            ->name('reports-center.index');

        Route::get('/documents/{document}/download', AdminDocumentDownloadController::class)
            ->middleware('permission:applications.view')
            ->name('documents.download');
        Route::get('/profile-documents/{document}/download', AdminApplicantProfileDocumentDownloadController::class)
            ->middleware('permission:applications.view')
            ->name('profile-documents.download');
        Route::get('/profile-documents/{document}/preview', AdminApplicantProfileDocumentPreviewController::class)
            ->middleware('permission:applications.view')
            ->name('profile-documents.preview');
    });
});

// Authenticated Applicant routes
Route::middleware(['auth', 'session.timeout', 'applicant', 'require2fa'])->prefix('applicant')->name('applicant.')->group(function () {
    Route::get('/dashboard', ApplicantDashboardController::class)->name('dashboard');

    // Profile
    Route::get('/profile', [ApplicantProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ApplicantProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ApplicantProfileController::class, 'update'])->name('profile.update');

    // Applications
    Route::get('/applications', [ApplicationController::class, 'index'])->name('applications.index');
    Route::get('/applications/{vacancy}/create', [ApplicationController::class, 'create'])->name('applications.create');
    Route::post('/applications/{vacancy}', [ApplicationController::class, 'store'])->middleware('throttle:10,1')->name('applications.store');
    Route::get('/applications/{application}', [ApplicationController::class, 'show'])->name('applications.show');
    Route::get('/applications/{application}/edit', [ApplicationController::class, 'edit'])->name('applications.edit');
    Route::put('/applications/{application}', [ApplicationController::class, 'update'])->name('applications.update');
    Route::post('/applications/{application}/documents/{document}/replace', [ApplicationController::class, 'replaceDocument'])->middleware('throttle:10,1')->name('applications.documents.replace');

    // Secure document download (application documents)
    Route::get('/documents/{document}/download', DocumentDownloadController::class)->name('documents.download');

    // Profile documents & photo
    Route::get('/profile/documents/{document}/download', ApplicantProfileDocumentController::class)
        ->name('profile.documents.download');
    Route::get('/profile/photo', ProfilePhotoController::class)->name('profile.photo');

    // Notifications
    Route::get('/notifications', [ApplicantNotificationController::class, 'index'])->name('notifications.index');

    // Vacancies (inside applicant area)
    Route::get('/vacancies', [ApplicantVacancyController::class, 'index'])->name('vacancies.index');
    Route::get('/vacancies/{vacancy}', [ApplicantVacancyController::class, 'show'])->name('vacancies.show');
});

Route::middleware(['auth', 'session.timeout'])->prefix('mfa')->name('mfa.')->group(function (): void {
    Route::get('/', [MfaController::class, 'show'])->name('show');
    Route::post('/enable', [MfaController::class, 'enable'])->middleware('throttle:10,1')->name('enable');
    Route::post('/disable', [MfaController::class, 'disable'])->middleware('throttle:10,1')->name('disable');
    Route::post('/recovery-codes', [MfaController::class, 'regenerateRecoveryCodes'])->middleware('throttle:10,1')->name('recovery-codes.regenerate');
    Route::get('/challenge', [MfaController::class, 'challenge'])->name('challenge');
    Route::post('/challenge', [MfaController::class, 'verify'])->middleware('throttle:10,1');
});
