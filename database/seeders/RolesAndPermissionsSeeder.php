<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = $this->getPermissions();

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $this->seedRoles($permissions);
    }

    private function getPermissions(): array
    {
        return [
            // Institution Management
            'institutions.view', 'institutions.create', 'institutions.update', 'institutions.delete',
            'institutions.activate', 'institutions.deactivate',

            // User Management
            'users.view', 'users.create', 'users.update', 'users.delete',
            'users.activate', 'users.deactivate', 'users.reset-password', 'users.assign-role',

            // Roles & Permissions
            'roles.view', 'roles.create', 'roles.update', 'roles.delete',
            'roles.assign-permissions', 'permissions.view', 'permissions.manage',

            // Vacancy Management
            'vacancies.view', 'vacancies.create', 'vacancies.update', 'vacancies.delete',
            'vacancies.publish', 'vacancies.close', 'vacancies.cancel', 'vacancies.archive',
            'vacancy-documents.manage', 'vacancy-questions.manage',

            // Application Management
            'applications.view', 'applications.view-sensitive', 'applications.update',
            'applications.delete', 'applications.export', 'applications.assign-reviewer',
            'applications.lock', 'applications.unlock',

            // Screening
            'screening.view', 'screening.review', 'screening.verify-documents',
            'screening.mark-passed', 'screening.mark-failed', 'screening.request-correction',
            'screening.reverse-decision', 'screening.view-history', 'screening.export',

            // Exams
            'exams.view', 'exams.create', 'exams.update', 'exams.delete',
            'exams.assign-applicants', 'exams.record-results',

            // Interviews
            'interviews.view', 'interviews.create', 'interviews.update', 'interviews.delete',
            'interviews.assign-applicants', 'interviews.record-results',

            // Notifications
            'notifications.view', 'notifications.create', 'notifications.send',
            'notifications.resend', 'notifications.templates.manage',

            // Dashboard & Reports
            'dashboard.view', 'reports.view', 'reports.export',
            'reports.applicants', 'reports.vacancies', 'reports.screening',
            'reports.exam-interview', 'reports.audit',

            // Content
            'content.view', 'content.manage',
            'sliders.view', 'sliders.create', 'sliders.update', 'sliders.delete', 'sliders.publish',
            'footer.manage', 'organization-info.manage',

            // Settings
            'settings.view', 'settings.manage', 'settings.localization',
            'settings.security', 'settings.notifications', 'settings.backup',

            // Audit Logs
            'audit.view', 'audit.export', 'audit.delete',

            // Applicant (used on applicant guard or as flags)
            'applicant.profile.view', 'applicant.profile.update',
            'applicant.vacancies.view', 'applicant.applications.create',
            'applicant.applications.view', 'applicant.applications.update',
            'applicant.documents.upload', 'applicant.documents.replace',
            'applicant.notifications.view', 'applicant.status.track',
        ];
    }

    private function seedRoles(array $permissions): void
    {
        // Super Admin — bypass all permission checks via gate in AuthServiceProvider
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions($permissions);

        // Admin
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions([
            'institutions.view', 'institutions.create', 'institutions.update',
            'institutions.activate', 'institutions.deactivate',
            'users.view', 'users.create', 'users.update', 'users.activate', 'users.deactivate',
            'users.reset-password', 'users.assign-role',
            'vacancies.view', 'vacancies.create', 'vacancies.update', 'vacancies.delete',
            'vacancies.publish', 'vacancies.close', 'vacancies.cancel', 'vacancies.archive',
            'vacancy-documents.manage',
            'applications.view', 'applications.view-sensitive', 'applications.update',
            'applications.export', 'applications.assign-reviewer', 'applications.lock',
            'screening.view', 'screening.review', 'screening.verify-documents',
            'screening.mark-passed', 'screening.mark-failed', 'screening.request-correction',
            'screening.reverse-decision', 'screening.view-history', 'screening.export',
            'exams.view', 'exams.create', 'exams.update', 'exams.delete',
            'exams.assign-applicants', 'exams.record-results',
            'interviews.view', 'interviews.create', 'interviews.update', 'interviews.delete',
            'interviews.assign-applicants', 'interviews.record-results',
            'notifications.view', 'notifications.create', 'notifications.send',
            'notifications.resend', 'notifications.templates.manage',
            'dashboard.view', 'reports.view', 'reports.export',
            'reports.applicants', 'reports.vacancies', 'reports.screening',
            'reports.exam-interview', 'reports.audit',
            'content.view', 'content.manage', 'sliders.view', 'sliders.create',
            'sliders.update', 'sliders.delete', 'sliders.publish',
            'footer.manage', 'organization-info.manage',
            'settings.view', 'settings.manage', 'settings.localization',
            'settings.security', 'settings.notifications', 'settings.backup',
            'audit.view', 'audit.export',
        ]);

        // HR Manager
        $hrManager = Role::firstOrCreate(['name' => 'hr_manager', 'guard_name' => 'web']);
        $hrManager->syncPermissions([
            'vacancies.view', 'vacancies.create', 'vacancies.update', 'vacancies.publish',
            'vacancies.close', 'vacancy-documents.manage',
            'applications.view', 'applications.view-sensitive', 'applications.export',
            'applications.assign-reviewer',
            'screening.view', 'screening.review', 'screening.verify-documents',
            'screening.mark-passed', 'screening.mark-failed', 'screening.request-correction',
            'screening.reverse-decision', 'screening.view-history', 'screening.export',
            'exams.view', 'exams.create', 'exams.update', 'exams.assign-applicants', 'exams.record-results',
            'interviews.view', 'interviews.create', 'interviews.update', 'interviews.assign-applicants', 'interviews.record-results',
            'notifications.view', 'notifications.create', 'notifications.send',
            'notifications.resend', 'notifications.templates.manage',
            'dashboard.view', 'reports.view', 'reports.export', 'reports.applicants',
            'reports.vacancies', 'reports.screening', 'reports.exam-interview',
            'audit.view', 'audit.export',
        ]);

        // HR Officer
        $hrOfficer = Role::firstOrCreate(['name' => 'hr_officer', 'guard_name' => 'web']);
        $hrOfficer->syncPermissions([
            'vacancies.view', 'vacancies.create', 'vacancies.update', 'vacancies.publish',
            'vacancies.close', 'vacancy-documents.manage',
            'applications.view', 'applications.export', 'applications.assign-reviewer',
            'screening.view', 'screening.review', 'screening.verify-documents',
            'screening.mark-passed', 'screening.mark-failed', 'screening.request-correction',
            'screening.view-history', 'screening.export',
            'exams.view', 'exams.assign-applicants',
            'interviews.view', 'interviews.assign-applicants',
            'notifications.view', 'notifications.create', 'notifications.send',
            'dashboard.view', 'reports.view', 'reports.export',
            'reports.applicants', 'reports.vacancies', 'reports.screening',
            'reports.exam-interview',
        ]);

        // Screening Officer
        $screeningOfficer = Role::firstOrCreate(['name' => 'screening_officer', 'guard_name' => 'web']);
        $screeningOfficer->syncPermissions([
            'vacancies.view',
            'applications.view',
            'screening.view', 'screening.review', 'screening.verify-documents',
            'screening.mark-passed', 'screening.mark-failed', 'screening.request-correction',
            'screening.view-history',
            'dashboard.view',
        ]);

        // Document Verifier
        $docVerifier = Role::firstOrCreate(['name' => 'document_verifier', 'guard_name' => 'web']);
        $docVerifier->syncPermissions([
            'vacancies.view', 'applications.view', 'screening.view',
            'screening.verify-documents', 'screening.view-history', 'dashboard.view',
        ]);

        // Exam Officer
        $examOfficer = Role::firstOrCreate(['name' => 'exam_officer', 'guard_name' => 'web']);
        $examOfficer->syncPermissions([
            'vacancies.view', 'applications.view',
            'exams.view', 'exams.create', 'exams.update', 'exams.delete',
            'exams.assign-applicants', 'exams.record-results',
            'notifications.view', 'notifications.send',
            'dashboard.view', 'reports.view', 'reports.exam-interview',
        ]);

        // Interview Officer
        $interviewOfficer = Role::firstOrCreate(['name' => 'interview_officer', 'guard_name' => 'web']);
        $interviewOfficer->syncPermissions([
            'vacancies.view', 'applications.view',
            'interviews.view', 'interviews.create', 'interviews.update', 'interviews.delete',
            'interviews.assign-applicants', 'interviews.record-results',
            'notifications.view', 'notifications.send',
            'dashboard.view', 'reports.view', 'reports.exam-interview',
        ]);

        // Report Viewer
        $reportViewer = Role::firstOrCreate(['name' => 'report_viewer', 'guard_name' => 'web']);
        $reportViewer->syncPermissions([
            'dashboard.view', 'reports.view', 'reports.export',
            'reports.applicants', 'reports.vacancies', 'reports.screening',
            'reports.exam-interview', 'audit.view',
        ]);

        // Content Manager
        $contentManager = Role::firstOrCreate(['name' => 'content_manager', 'guard_name' => 'web']);
        $contentManager->syncPermissions([
            'content.view', 'content.manage',
            'sliders.view', 'sliders.create', 'sliders.update', 'sliders.delete', 'sliders.publish',
            'footer.manage', 'organization-info.manage', 'settings.localization',
        ]);

        // Applicant role (for applicant guard)
        $applicantRole = Role::firstOrCreate(['name' => 'applicant', 'guard_name' => 'web']);
        $applicantRole->syncPermissions([
            'applicant.profile.view', 'applicant.profile.update',
            'applicant.vacancies.view', 'applicant.applications.create',
            'applicant.applications.view', 'applicant.applications.update',
            'applicant.documents.upload', 'applicant.documents.replace',
            'applicant.notifications.view', 'applicant.status.track',
        ]);
    }
}
