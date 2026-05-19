<?php

declare(strict_types=1);

return [
    // ── Dashboard ────────────────────────────────────────────────────────────
    'welcome' => 'Welcome, :name',
    'what_today' => 'What would you like to do today?',
    'total_applications' => 'Total',
    'active_applications' => 'Active',
    'passed_applications' => 'Passed',
    'rejected_applications' => 'Rejected',
    'recent_applications' => 'Recent Applications',
    'no_applications_yet' => 'No applications submitted yet.',
    'quick_actions' => 'Quick Actions',
    'browse_jobs' => 'Browse Jobs',

    // ── Profile completion ───────────────────────────────────────────────────
    'profile_complete' => 'Your profile is complete.',
    'profile_incomplete' => 'Complete your profile before applying.',
    'profile_completion' => 'Profile Completion',
    'complete_profile' => 'Complete Profile',
    'missing_fields' => 'Missing fields:',
    'completion_pct' => ':pct% complete',

    // ── Profile ──────────────────────────────────────────────────────────────
    'profile_heading' => 'My Profile',
    'profile_subtitle' => 'Manage your personal information and documents.',
    'edit_profile' => 'Edit Profile',
    'personal_info' => 'Personal Information',
    'education_info' => 'Education',
    'work_info' => 'Work Experience',
    'contact_info' => 'Contact & Address',
    'full_name' => 'Full Name',
    'national_id' => 'National ID',
    'date_of_birth' => 'Date of Birth',
    'gender' => 'Gender',
    'phone' => 'Phone Number',
    'address' => 'Address',
    'disability' => 'Disability',
    'disability_detail' => 'Disability Type',
    'not_provided' => 'Not provided',
    'save_changes' => 'Save Changes',
    'cancel' => 'Cancel',
    'profile_updated' => 'Profile updated successfully.',

    // ── Applications ─────────────────────────────────────────────────────────
    'my_applications' => 'My Applications',
    'no_applications' => 'You have no applications yet.',
    'start_applying' => 'Browse open vacancies to get started.',
    'ref_number' => 'Reference',
    'submitted_at' => 'Submitted',
    'view_application' => 'View',
    'edit_application' => 'Edit',
    'application_detail' => 'Application Details',
    'back_to_applications' => '← Back to My Applications',
    'status_timeline' => 'Status Timeline',
    'uploaded_documents' => 'Uploaded Documents',
    'no_documents' => 'No documents uploaded.',
    'verified' => 'Verified',
    'rejected_doc' => 'Rejected',
    'pending_doc' => 'Pending review',

    // ── Create / Edit application ─────────────────────────────────────────────
    'apply_for' => 'Apply for: :title',
    'upload_document' => 'Upload Document',
    'replace_document' => 'Replace Document',
    'choose_file' => 'Choose file',
    'allowed_formats' => 'PDF, JPG, JPEG, PNG — max 2 MB',
    'submit_application' => 'Submit Application',
    'save_draft' => 'Save Draft',
    'correction_required_note' => 'Action Required: Your application needs correction.',
    'locked_notice' => 'This application can no longer be edited. The vacancy deadline has passed.',

    // ── Notifications ─────────────────────────────────────────────────────────
    'notifications_heading' => 'Notifications',
    'no_notifications' => 'No notifications yet.',
    'mark_all_read' => 'Mark all as read',
    'unread' => 'Unread',

    // ── Auth ──────────────────────────────────────────────────────────────────
    'sign_in' => 'Sign in to your account',
    'new_here' => 'New here?',
    'create_account' => 'Create an account',
    'sign_in_button' => 'Sign in',
    'remember_me' => 'Remember me',
    'enter_email' => 'Enter your email',
    'enter_password' => 'Enter your password',
    'back_to_jobs' => '← Back to job vacancies',
    'register_heading' => 'Create your applicant account',
    'register_subheading' => 'Fill in all sections to complete your profile. You can update your details later.',
    'already_have_account' => 'Already have an account?',
    'sign_in_link' => 'Sign in',
    'register_button' => 'Submit Registration',

    // ── Registration steps ────────────────────────────────────────────────────
    'step_personal' => 'Personal',
    'step_education' => 'Education',
    'step_work' => 'Work',
    'step_contact' => 'Contact',
    'step_documents' => 'Documents',
    'step_review' => 'Review',

    'step_1_heading' => 'Personal Information',
    'step_2_heading' => 'Education',
    'step_3_heading' => 'Work Experience',
    'step_4_heading' => 'Contact & Account',
    'step_5_heading' => 'Documents',
    'step_6_heading' => 'Review & Submit',

    'step_next' => 'Continue',
    'step_back' => 'Back',
    'step_of' => 'Step :current of :total',

    // ── Registration: disability ──────────────────────────────────────────────
    'disability_yes' => 'Yes',
    'disability_no' => 'No',
    'disability_type_hint' => 'Please describe the type of disability.',

    // ── Registration: education levels ────────────────────────────────────────
    'edu_certificate' => 'Certificate',
    'edu_diploma' => 'Diploma',
    'edu_degree' => 'Bachelor\'s Degree',
    'edu_masters' => 'Master\'s Degree',
    'edu_phd' => 'PhD / Doctorate',

    // ── Registration: documents ───────────────────────────────────────────────
    'doc_section_profile' => 'Profile Photo',
    'doc_section_identity' => 'Identity & Qualification Documents',
    'doc_photo_hint' => 'JPG or PNG, max 2 MB. Will be used in your applicant profile.',
    'doc_hint' => 'PDF, JPG, JPEG, or PNG — max 2 MB per file.',
    'doc_combined_hint' => 'Combine all your documents (CV, ID, certificates, etc.) into one PDF — max 2 MB.',
    'doc_optional' => '(optional)',
    'doc_required_disability' => 'Required when disability status is Yes.',
    'doc_not_uploaded' => 'Not uploaded yet.',
    'doc_uploaded' => 'Uploaded',
    'doc_replace' => 'Replace file (PDF/JPG/PNG ≤ 2 MB)',
    'doc_upload_new' => 'Upload file (PDF/JPG/PNG ≤ 2 MB)',

    // ── Registration: review ──────────────────────────────────────────────────
    'review_personal' => 'Personal Information',
    'review_education' => 'Education',
    'review_work' => 'Work Experience',
    'review_contact' => 'Contact & Account',
    'review_documents' => 'Documents',
    'review_edit' => 'Edit',
    'terms_label' => 'I agree to the Terms of Use and Privacy Policy.',
    'terms_required' => 'You must accept the terms to register.',

    // ── Profile edit: application form fields ────────────────────────────────
    'apply_for_position' => 'Apply for Position',
    'edit_application_title' => 'Edit Application',
    'academic_info' => 'Academic Information',
    'replace_documents' => 'Replace Documents',
    'current_file' => 'Current',
    'replace' => 'Replace',
    'optional' => 'optional',
    'max_size' => 'Max',
    'cgpa_optional' => 'CGPA (optional)',
    'preferred_language' => 'Preferred Language',
    'ethnicity_optional' => 'Ethnicity (optional)',
    'address_optional' => 'Address (optional)',
    'disability_label' => 'I have a disability',
];
