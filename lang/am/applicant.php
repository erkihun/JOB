<?php

declare(strict_types=1);

return [
    // ── Dashboard ────────────────────────────────────────────────────────────
    'welcome' => 'እንኳን ደህና መጡ፣ :name',
    'what_today' => 'ዛሬ ምን ማድረግ ይፈልጋሉ?',
    'total_applications' => 'ጠቅላላ',
    'active_applications' => 'በሂደት ላይ',
    'passed_applications' => 'ያለፉ',
    'rejected_applications' => 'ተቀባይነት አላገኘም',
    'recent_applications' => 'የቅርብ ጊዜ ማመልከቻዎች',
    'no_applications_yet' => 'እስካሁን ምንም ማመልከቻ አልቀረበም።',
    'quick_actions' => 'ፈጣን እርምጃዎች',
    'browse_jobs' => 'ስራዎችን ፈልጉ',

    // ── Profile completion ───────────────────────────────────────────────────
    'profile_complete' => 'አስፈላጊ መረጃ ተሟልቷል።',
    'profile_incomplete' => 'ከማመልከቱ በፊት አስፈላጊ መረጃዎችን ያሟሉ።',
    'profile_completion' => 'የተሟላ መረጃ',
    'complete_profile' => 'መረጃ ያሟሉ',
    'missing_fields' => 'ያልተሞሉ ቦታዎች:',
    'completion_pct' => ':pct% ተሟልቷል',

    // ── Profile ──────────────────────────────────────────────────────────────
    'profile_heading' => 'የግል መረጃ',
    'profile_subtitle' => 'የግል መረጃዎን እና ሰነዶችዎን ያስተዳድሩ።',
    'edit_profile' => 'የግል መረጃ አስተካከል',
    'personal_info' => 'የግል መረጃ',
    'education_info' => 'የትምህርት ሁኔታ',
    'work_info' => 'የስራ ልምድ',
    'contact_info' => 'መገኛ አድራሻ',
    'full_name' => 'ሙሉ ስም',
    'national_id' => 'ብሔራዊ መታወቂያ',
    'date_of_birth' => 'የልደት ቀን',
    'gender' => 'ጾታ',
    'phone' => 'ስልክ ቁጥር',
    'address' => 'አድራሻ',
    'disability' => 'የአካል ጉዳት',
    'disability_detail' => 'የጉዳት ዝርዝር',
    'not_provided' => 'አልተሞላም',
    'save_changes' => 'ለውጦችን አስቀምጥ',
    'cancel' => 'ሰርዝ',
    'profile_updated' => 'መለያ በተሳካ ሁኔታ ተዘምኗል።',

    // ── Applications ─────────────────────────────────────────────────────────
    'my_applications' => 'ማመልከቻዎቼ',
    'no_applications' => 'እስካሁን ምንም ማመልከቻ የለም።',
    'start_applying' => 'ለማመልከት ክፍት ቦታዎችን ይፈልጉ።',
    'ref_number' => 'ማቅርቢያ ቁጥር',
    'submitted_at' => 'የቀረበ',
    'view_application' => 'ይመልከቱ',
    'edit_application' => 'አርትዕ',
    'application_detail' => 'የማመልከቻ ዝርዝር',
    'back_to_applications' => '← ወደ ማመልከቻዎቼ ተመለስ',
    'status_timeline' => 'የሁኔታ ታሪክ',
    'uploaded_documents' => 'የተሰቀሉ ሰነዶች',
    'no_documents' => 'ምንም ሰነድ አልተሰቀለም።',
    'verified' => 'ተረጋግጧል',
    'rejected_doc' => 'ተቀባይነት አላገኘም',
    'pending_doc' => 'ግምገማ በጥበቃ ላይ',

    // ── Create / Edit application ─────────────────────────────────────────────
    'apply_for' => 'ያመልክቱ ለ: :title',
    'upload_document' => 'ሰነድ ስቀሉ',
    'replace_document' => 'ሰነድ ይቀይሩ',
    'choose_file' => 'ፋይል ይምረጡ',
    'allowed_formats' => 'PDF, JPG, JPEG, PNG — ከፍተኛ 2 MB',
    'submit_application' => 'ማመልከቻ አቅርብ',
    'save_draft' => 'ረቂቅ አስቀምጥ',
    'correction_required_note' => 'ጥሪ: ማመልከቻዎ ማስተካከያ ያስፈልገዋል።',
    'locked_notice' => 'ይህ ማመልከቻ ከዚህ በኋላ ሊስተካከል አይችልም። የቦታ የማብቂያ ቀን አልፏል።',

    // ── Notifications ─────────────────────────────────────────────────────────
    'notifications_heading' => 'ማሳወቂያዎች',
    'no_notifications' => 'እስካሁን ምንም ማሳወቂያ የለም።',
    'mark_all_read' => 'ሁሉንም እንደ ተነቡ ምልክት አድርግ',
    'unread' => 'ያልተነቡ',

    // ── Relative time (used by et_diff_for_humans()) ──────────────────────────
    'just_now'       => 'ድሜ',
    'ago'            => 'በፊት',
    'from_now'       => 'ቆይቶ',
    'minute_one'     => 'ከ1 ደቂቃ',
    'minutes_many'   => 'ከ:n ደቂቃዎች',
    'hour_one'       => 'ከ1 ሰዓት',
    'hours_many'     => 'ከ:n ሰዓቶች',
    'day_one'        => 'ከ1 ቀን',
    'days_many'      => 'ከ:n ቀናት',
    'week_one'       => 'ከ1 ሳምንት',
    'weeks_many'     => 'ከ:n ሳምንታት',
    'month_one'      => 'ከ1 ወር',
    'months_many'    => 'ከ:n ወራት',
    'year_one'       => 'ከ1 ዓመት',
    'years_many'     => 'ከ:n ዓመታት',

    // ── Auth ──────────────────────────────────────────────────────────────────
    'sign_in' => 'ወደ መለያዎ ይግቡ',
    'new_here' => 'አዲስ ተጠቃሚ ነዎት?',
    'create_account' => 'መዝግቡ',
    'sign_in_button' => 'ግባ',
    'remember_me' => 'አስታውሰኝ',
    'enter_email' => 'ኢሜልዎን ያስገቡ',
    'enter_password' => 'የይለፍ ቃልዎን ያስገቡ',
    'back_to_jobs' => '← ወደ ክፍት ስራዎች ተመለስ',
    'register_heading' => 'የአመልካች መለያ ፍጠሩ',
    'register_subheading' => 'ሁሉንም ክፍሎች ይሙሉ። ዝርዝሮቹን በኋላ ማዘምን ይችላሉ።',
    'already_have_account' => 'መለያ አስቀድሞ አለዎት?',
    'sign_in_link' => 'ይግቡ',
    'register_button' => 'ምዝገባ አስገባ',

    // ── Registration steps ────────────────────────────────────────────────────
    'step_personal' => 'ግላዊ መረጃ',
    'step_education' => 'የ ትምህርት ሁኔታ',
    'step_work' => 'የስራ ልምድ',
    'step_contact' => 'መገኛ አድራሻ',
    'step_documents' => 'አስፈላጊ ሰነዶች',
    'step_review' => 'ገምግም',

    'step_1_heading' => 'ግላዊ መረጃ',
    'step_2_heading' => 'የ ትምህርት ሁኔታ',
    'step_3_heading' => 'የስራ ልምድ',
    'step_4_heading' => 'መገኛ አድራሻ',
    'step_5_heading' => 'አስፈላጊ ሰነዶች',
    'step_6_heading' => 'ይገምግሙ እና ያስገቡ',

    'step_next' => 'ቀጥል',
    'step_back' => 'ተመለስ',
    'step_of' => 'ደረጃ :current ከ :total',

    // ── Registration: disability ──────────────────────────────────────────────
    'disability_yes' => 'አዎ',
    'disability_no' => 'አይደለም',
    'disability_type_hint' => 'የጉዳቱን ዓይነት ይግለጹ።',

    // ── Registration: education levels ────────────────────────────────────────
    'edu_certificate' => 'ሰርተፍኬት',
    'edu_diploma' => 'ዲፕሎማ',
    'edu_degree' => 'የመጀመሪያ ዲግሪ',
    'edu_masters' => 'ሁለተኛ ዲግሪ',
    'edu_phd' => 'ዶክትሬት',

    // ── Registration: documents ───────────────────────────────────────────────
    'doc_section_profile' => 'የፕሮፋይል ፎቶ',
    'doc_section_identity' => 'መታወቂያ እና ማረጋገጫ ሰነዶች',
    'doc_photo_hint' => 'JPG ወይም PNG፣ ከፍተኛ 2 MB። በፕሮፋይልዎ ውስጥ ይሰፍናል።',
    'doc_hint' => 'PDF፣ JPG፣ JPEG ወይም PNG — ከፍተኛ 2 MB።',
    'doc_combined_hint' => 'የተጠየቁ ዶክመንቶችን ወደ አንድ PDF ማድረግ — ከፍተኛ 2 MB።',
    'doc_optional' => '(አማራጭ)',
    'doc_required_disability' => 'የአካል ጉዳት ሁኔታ "አዎ" ሲሆን ያስፈልጋል።',
    'doc_not_uploaded' => 'እስካሁን አልተጫነም።',
    'doc_uploaded' => 'ተጭኗል',
    'doc_replace' => 'ፋይል ይቀይሩ (PDF/JPG/PNG ≤ 2 MB)',
    'doc_upload_new' => 'ፋይል ይጫኑ (PDF/JPG/PNG ≤ 2 MB)',

    // ── Registration: review ──────────────────────────────────────────────────
    'review_personal' => 'የግል መረጃ',
    'review_education' => 'የትምህርት ሁኔታ',
    'review_work' => 'የስራ ልምድ',
    'review_contact' => 'መገኛ አድራሻ',
    'review_documents' => 'ሰነዶች',
    'review_edit' => 'አስተካክል',
    'terms_label' => 'የአጠቃቀም ውሎችን እና የፕሪቬሲ ፖሊሲን ተቀብያለሁ።',
    'terms_required' => 'ለመመዝገብ ውሎቹን መቀበል አለብዎ።',

    // ── Profile edit: application form fields ────────────────────────────────
    'apply_for_position' => 'ለስራ ማመልከት',
    'edit_application_title' => 'ማመልከቻ አስተካክል',
    'academic_info' => 'የትምህርት መረጃ',
    'replace_documents' => 'ሰነዶችን ተካ',
    'current_file' => 'አሁን ያለው',
    'replace' => 'ተካ',
    'optional' => 'አማራጭ',
    'max_size' => 'ከፍተኛ',
    'cgpa_optional' => 'ሲጂፒኤ (አማራጭ)',
    'preferred_language' => 'ቋንቋ ምርጫ',
    'ethnicity_optional' => 'ብሔር (አማራጭ)',
    'address_optional' => 'አድራሻ (አማራጭ)',
    'disability_label' => 'የአካል ጉዳት አለብኝ',

    // ── Status timeline labels ─────────────────────────────────────────────────
    'timeline_submitted' => 'ቀርቧል',
    'timeline_review' => 'በሂደት ላይ',
    'timeline_screening' => 'ምልመላ',
    'timeline_exam' => 'ፈተና / ቃለ-መጠይቅ',
    'timeline_result' => 'የመጨረሻ ውጤት',

    // ── Application list ──────────────────────────────────────────────────────
    'applications_count' => ':count ማመልከቻ',
];
