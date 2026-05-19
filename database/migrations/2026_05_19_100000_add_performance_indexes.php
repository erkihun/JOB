<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Performance indexes for 30,000+ applicant scale.
 *
 * Foreign keys auto-create indexes on most engines, but composite/solo
 * indexes for common filter/sort columns must be added explicitly.
 * This migration is additive-only and safe on existing installs.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── applications ────────────────────────────────────────────────────
        Schema::table('applications', function (Blueprint $table) {
            // vacancy_id solo: queries by vacancy (screening list, export filters)
            $table->index('vacancy_id', 'idx_applications_vacancy_id');
            // status: screening list filters, dashboard stats
            $table->index('status', 'idx_applications_status');
            // submitted_at: ordering and date-range reports
            $table->index('submitted_at', 'idx_applications_submitted_at');
            // screening_status: passed/failed export filters
            $table->index('screening_status', 'idx_applications_screening_status');
            // screened_at: audit timeline queries
            $table->index('screened_at', 'idx_applications_screened_at');
        });

        // ── vacancies ────────────────────────────────────────────────────────
        Schema::table('vacancies', function (Blueprint $table) {
            // status: public listing, dashboard open count
            $table->index('status', 'idx_vacancies_status');
            // closing_date: deadline checks and ordering
            $table->index('closing_date', 'idx_vacancies_closing_date');
            // opening_date: admin ordering
            $table->index('opening_date', 'idx_vacancies_opening_date');
            // published_at: home page ordering
            $table->index('published_at', 'idx_vacancies_published_at');
        });

        // ── applicant_notifications ──────────────────────────────────────────
        Schema::table('applicant_notifications', function (Blueprint $table) {
            // read_at: unread count badge (IS NULL filter)
            $table->index('read_at', 'idx_applicant_notifications_read_at');
            // status: sent/pending/failed filters
            $table->index('status', 'idx_applicant_notifications_status');
            // composite: fetch unread for one applicant (most common query)
            $table->index(['applicant_id', 'read_at'], 'idx_applicant_notifications_applicant_read');
        });

        // ── audit_logs ───────────────────────────────────────────────────────
        Schema::table('audit_logs', function (Blueprint $table) {
            // user_id: filter by actor
            $table->index('user_id', 'idx_audit_logs_user_id');
            // module: filter by section
            $table->index('module', 'idx_audit_logs_module');
            // action: filter by action type
            $table->index('action', 'idx_audit_logs_action');
            // created_at: date-range queries, ordering
            $table->index('created_at', 'idx_audit_logs_created_at');
        });

        // ── screening_reviews ────────────────────────────────────────────────
        Schema::table('screening_reviews', function (Blueprint $table) {
            // reviewer_id solo: reviewer history queries
            $table->index('reviewer_id', 'idx_screening_reviews_reviewer_id');
            // reviewed_at: timeline ordering
            $table->index('reviewed_at', 'idx_screening_reviews_reviewed_at');
        });

        // ── application_documents ────────────────────────────────────────────
        Schema::table('application_documents', function (Blueprint $table) {
            // vacancy_document_id solo: document verification lookups
            $table->index('vacancy_document_id', 'idx_application_documents_vacancy_doc_id');
            // verification_status: admin document review filters
            $table->index('verification_status', 'idx_application_documents_verification_status');
        });

        // ── applicant_profile_documents ──────────────────────────────────────
        Schema::table('applicant_profile_documents', function (Blueprint $table) {
            // document_type: type-based lookups (profile completion check)
            $table->index(['applicant_id', 'document_type'], 'idx_profile_docs_applicant_type');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropIndex('idx_applications_vacancy_id');
            $table->dropIndex('idx_applications_status');
            $table->dropIndex('idx_applications_submitted_at');
            $table->dropIndex('idx_applications_screening_status');
            $table->dropIndex('idx_applications_screened_at');
        });

        Schema::table('vacancies', function (Blueprint $table) {
            $table->dropIndex('idx_vacancies_status');
            $table->dropIndex('idx_vacancies_closing_date');
            $table->dropIndex('idx_vacancies_opening_date');
            $table->dropIndex('idx_vacancies_published_at');
        });

        Schema::table('applicant_notifications', function (Blueprint $table) {
            $table->dropIndex('idx_applicant_notifications_read_at');
            $table->dropIndex('idx_applicant_notifications_status');
            $table->dropIndex('idx_applicant_notifications_applicant_read');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('idx_audit_logs_user_id');
            $table->dropIndex('idx_audit_logs_module');
            $table->dropIndex('idx_audit_logs_action');
            $table->dropIndex('idx_audit_logs_created_at');
        });

        Schema::table('screening_reviews', function (Blueprint $table) {
            $table->dropIndex('idx_screening_reviews_reviewer_id');
            $table->dropIndex('idx_screening_reviews_reviewed_at');
        });

        Schema::table('application_documents', function (Blueprint $table) {
            $table->dropIndex('idx_application_documents_vacancy_doc_id');
            $table->dropIndex('idx_application_documents_verification_status');
        });

        Schema::table('applicant_profile_documents', function (Blueprint $table) {
            $table->dropIndex('idx_profile_docs_applicant_type');
        });
    }
};
