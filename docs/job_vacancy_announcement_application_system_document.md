# Job Vacancy Announcement and Application System

**System Requirements and Design Document**  
**Version:** 1.0  
**System Type:** Web-based recruitment management system  
**Target Users:** Admin, HR/Recruitment Officers, Screening Committee, Applicants

---

## 1. Project Overview

The Job Vacancy Announcement and Application System is a web-based platform designed to manage the full recruitment process from vacancy publication to applicant screening, shortlisting, exam/interview notification, and application status tracking.

The system provides two main interfaces:

1. **Admin Interface**  
   Used by authorized staff to manage users, vacancies, applications, screening, notifications, reports, and system settings.

2. **Applicant Interface**  
   Used by job seekers to view open vacancies, register, apply, upload required documents, update their application before the deadline, and track application progress.

The system improves transparency, reduces manual paperwork, prevents duplicate applications, and provides accurate recruitment statistics.

---

## 2. Objectives

The main objectives of the system are:

- Publish job vacancies online.
- Allow applicants to apply for open vacancies.
- Prevent applicants from applying more than once to the same vacancy.
- Allow applicants to update their submitted information until the vacancy closing date.
- Enforce strict document upload rules, including a maximum file size of **2 MB**.
- Allow Admin users to review applicant information and documents.
- Separate applicants who pass initial screening from those who fail.
- Notify shortlisted applicants for exam or interview.
- Provide applicants with application tracking.
- Provide dashboards, reports, and recruitment analytics.
- Manage roles, permissions, and system users securely.
- Keep recruitment data organized, searchable, auditable, and exportable.

---

## 3. System Scope

### 3.1 Included in Scope

The system includes:

- Public landing page
- Organization logo/header
- Hero slider managed by Admin
- Vacancy listing
- Applicant registration and login
- Online job application form
- Required document uploads
- Applicant dashboard
- Application tracking
- Admin dashboard
- User management
- Vacancy management
- Screening and verification
- Screening status management
- Exam/interview notification
- System settings
- Role-based permission management
- Reports and analytics
- Audit logs
- Email/SMS/in-system notifications

### 3.2 Out of Scope for Initial Version

The following can be planned for later versions:

- Online exam integration
- Payment integration
- AI-based document verification
- Biometric identity verification
- Integration with national ID APIs
- Payroll or employee onboarding system
- Video interview system

---

## 4. User Roles

### 4.1 Applicant

The Applicant can:

- View organization information.
- View open vacancies.
- Register or login.
- Apply for vacancies.
- Upload required documents.
- Update submitted information before vacancy closing date.
- Track application status.
- Receive exam/interview notifications.
- View screening result.
- View final recruitment result, if published.

### 4.2 Super Admin

The Super Admin has full system access.

Responsibilities:

- Manage all system users.
- Manage roles and permissions.
- Configure system settings.
- Manage vacancies.
- View all applications.
- View reports and dashboards.
- Manage landing page content.
- Manage notification settings.
- Review audit logs.

### 4.3 HR Admin / Recruitment Officer

The HR Admin can:

- Create and manage vacancies.
- Review applications.
- Verify applicant documents.
- Change screening status.
- Shortlist applicants.
- Send exam/interview notifications.
- Generate recruitment reports.

### 4.4 Screening Committee

The Screening Committee can:

- View assigned applicants.
- Review applicant details.
- Check uploaded documents.
- Mark applicants as passed or failed.
- Add screening remarks.
- Request correction or additional information.

### 4.5 Viewer / Report Officer

The Viewer can:

- View dashboards.
- View applicants.
- Export reports.
- Cannot create, update, or delete core records unless permission is granted.

---

## 5. Main System Modules

## 5.1 Public Landing Page

The landing page is the first page visitors see.

### Main Components

| Component | Description |
|---|---|
| Header | Displays organization logo, name, navigation toolbar, and login button |
| Hero Slider | Displays banners, announcements, and recruitment highlights |
| Job List | Displays open vacancies |
| About Section | Displays organization information |
| Footer | Displays address, contact information, links, copyright, and social links |

### Admin-Managed Landing Page Content

Admin should be able to manage:

- Organization logo
- Organization name
- Hero slider images
- Slider title and description
- Slider button text and link
- Footer content
- Contact information
- Social media links
- Public announcement messages

---

## 5.2 Applicant Registration and Login

Applicants must register before applying.

### Registration Fields

| Field | Required | Validation |
|---|---:|---|
| Full Name | Yes | Text only, minimum 2 words |
| Phone Number | Yes | Unique, valid format |
| Email | Yes | Unique, valid email |
| Password | Yes | Strong password |
| Confirm Password | Yes | Must match password |

### Recommended Improvements

- Email verification
- Phone OTP verification
- Password reset
- CAPTCHA protection
- Login attempt throttling
- Applicant account lockout after repeated failed attempts

---

## 5.3 Job Vacancy Management

Admin can create and manage job vacancies.

### Vacancy Fields

| Field | Description |
|---|---|
| Job Title | Name of the position |
| Job Code | Unique vacancy code |
| Department / Directorate | Hiring department |
| Employment Type | Permanent, contract, temporary, internship, etc. |
| Job Location | Work location |
| Number of Positions | Number of employees required |
| Salary / Grade | Optional |
| Job Description | Duties and responsibilities |
| Qualification Requirements | Required education and experience |
| Field of Study | Accepted graduation fields |
| Required Experience | Minimum experience |
| Required Documents | Documents applicants must upload |
| Opening Date | Application start date |
| Closing Date | Application deadline |
| Status | Draft, Open, Closed, Screening, Cancelled |
| Published By | Admin user who published it |

### Vacancy Statuses

| Status | Meaning |
|---|---|
| Draft | Created but not visible to applicants |
| Open | Visible and accepting applications |
| Closed | Deadline passed; no new application or editing allowed |
| Screening | Applications are being reviewed |
| Exam Stage | Applicants are being invited for exam |
| Interview Stage | Applicants are being invited for interview |
| Finalized | Recruitment process completed |
| Cancelled | Vacancy cancelled |

### Business Rules

- Applicants can only apply when the vacancy status is **Open**.
- Applicants cannot apply after the closing date.
- Applicants cannot edit their application after the closing date.
- Admin can close a vacancy manually.
- Closed vacancies should remain visible as archived vacancies if the organization wants transparency.
- A vacancy cannot be deleted if applicants already applied; it should be archived instead.

---

## 5.4 Applicant Job List

Applicants and public visitors can view open vacancies.

### Displayed Information

Each job card/list item should show:

- Job title
- Job code
- Department
- Location
- Number of positions
- Opening date
- Closing date
- Short description
- “View Details” button
- “Apply Now” button

### Filters

Applicants should be able to filter jobs by:

- Job title
- Department
- Field of study
- Employment type
- Location
- Opening/closing date

---

## 6. Application Form

Applicants must submit their personal, academic, identity, and document information.

### 6.1 Required Applicant Information

| Field | Required | Notes |
|---|---:|---|
| Full Name | Yes | As written on official document |
| Phone Number | Yes | Must be unique or verified |
| Email | Yes | Must be unique or verified |
| Field of Study / Graduation Type | Yes | Example: Computer Science, Accounting, Law |
| Graduation Date | Yes | Date picker |
| National ID | Yes | Must be unique |
| Disability Status | Yes | Yes / No |
| Gender | Yes | Male / Female / Other depending on organization policy |
| Ethnicity | Yes/Optional | Should be handled carefully due to privacy sensitivity |
| Address | Recommended | Region, city, woreda/sub-city |
| Work Experience | Recommended | Years/months of experience |
| CGPA / Result | Optional | Depending on vacancy requirement |

### 6.2 Required Document Uploads

The system should allow Admin to define required documents per vacancy.

### Example Required Documents

- CV / Resume
- Degree certificate
- Student copy / transcript
- National ID
- Work experience letter
- Professional license
- Disability support document, when applicable
- Other supporting documents

### Upload Rules

| Rule | Requirement |
|---|---|
| Maximum file size | 2 MB per file |
| Allowed file types | PDF, JPG, JPEG, PNG |
| File naming | System should rename files securely |
| Storage | Private storage, not publicly accessible |
| Required documents | Applicant cannot submit without required documents |
| Replacement | Applicant can replace documents before closing date |
| Virus/security scanning | Recommended |
| Preview | Admin should preview documents during screening |

### Important Rule

The file upload validation must strictly reject files larger than **2 MB**.

---

## 7. Application Rules

### 7.1 Prevent Duplicate Application

An applicant must not apply more than once to the same vacancy.

### Rule

The system should enforce uniqueness using:

- Applicant account ID
- Vacancy ID
- National ID
- Email
- Phone number

Recommended unique rule:

```text
One applicant account can have only one application per vacancy.
```

Additional duplicate detection should compare:

- National ID
- Email
- Phone number

This helps detect users creating multiple accounts to apply again.

---

### 7.2 Editing Application Before Closing Date

Applicants should be allowed to edit their submitted application until the vacancy closing date.

### Editable Before Closing Date

Applicants may update:

- Personal information
- Phone number
- Email
- Field of study
- Graduation date
- Disability status
- Gender
- Ethnicity
- Uploaded documents
- Vacancy-specific choices

### Not Editable After Closing Date

Once the closing date passes:

- Application becomes locked.
- Documents cannot be replaced.
- Applicant information related to that vacancy cannot be changed.
- Admin can still add screening remarks and status updates.

---

### 7.3 Application Reference Number

After successful submission, the system should generate a unique application reference number.

Example:

```text
APP-2026-000123
```

Applicants can use this reference number to track their application.

---

## 8. Application Status Tracking

Applicants should be able to check the status of their application.

### 8.1 Applicant Tracking Page

Applicant can track status using:

- Login account, or
- Application reference number plus phone/email verification

### 8.2 Application Statuses

| Status | Meaning |
|---|---|
| Submitted | Application successfully submitted |
| Under Review | Screening committee is reviewing the application |
| Correction Required | Applicant must update missing/incorrect information before deadline |
| Passed Initial Screening | Applicant passed document/information screening |
| Failed Initial Screening | Applicant did not pass initial screening |
| Shortlisted for Exam | Applicant is invited for exam |
| Exam Completed | Exam result recorded |
| Shortlisted for Interview | Applicant is invited for interview |
| Interview Completed | Interview result recorded |
| Selected | Applicant selected for the position |
| Waitlisted | Applicant placed on reserve list |
| Not Selected | Applicant not selected |
| Withdrawn | Applicant withdrew application |

---

## 9. Information Screening / Filtering Module

This is one of the most important Admin-side modules.

### 9.1 Screening Page Features

Admin or Screening Committee can:

- View applicant list per vacancy.
- Search applicants by name, phone, email, National ID, or reference number.
- Filter by application status.
- Filter by field of study.
- Filter by gender.
- Filter by disability status.
- Filter by graduation date.
- Filter by document completion status.
- Preview uploaded documents.
- Mark documents as verified or rejected.
- Add screening remarks.
- Change screening result.

### 9.2 Screening Decision Options

| Decision | Description |
|---|---|
| Passed | Applicant meets initial requirements |
| Failed | Applicant does not meet requirements |
| Correction Required | Applicant must fix missing or invalid information |
| Pending | Not reviewed yet |

### 9.3 Screening Remarks

Each screening decision should include remarks.

Example remarks:

- Missing degree certificate
- Field of study not eligible
- Graduation date not within required range
- Document unreadable
- Experience requirement not met
- Duplicate application detected
- Information verified

### 9.4 Screening History

The system should store screening history:

- Who reviewed the applicant
- Review date and time
- Previous status
- New status
- Remarks
- Document verification result

This improves accountability and transparency.

---

## 10. Passed and Failed Screening Display

The system should provide separate lists for applicants based on screening outcome.

### 10.1 Passed Applicants Page

Displays applicants who passed initial screening.

Features:

- Filter by vacancy
- Export list to Excel/PDF
- Send exam notification
- Send interview notification
- Move to next recruitment stage

### 10.2 Failed Applicants Page

Displays applicants who failed initial screening.

Features:

- Filter by vacancy
- View rejection reason
- Export failed applicants
- Optionally notify applicants
- Allow Admin to reconsider or reverse decision with permission

### 10.3 Pending Applicants Page

Displays applicants not yet screened.

Features:

- Assign to screening officers
- Bulk status update
- Review documents

---

## 11. Exam and Interview Notification

The system should notify applicants when they are shortlisted.

### 11.1 Notification Channels

Recommended channels:

- Email
- SMS
- In-system notification
- Applicant dashboard alert

### 11.2 Notification Content

Notification should include:

- Applicant name
- Vacancy title
- Exam/interview type
- Date
- Time
- Venue
- Instructions
- Required documents to bring
- Contact information

### 11.3 Example Notification

```text
Dear Applicant,

You have been shortlisted for the written exam for the position of Software Developer.

Exam Date: June 12, 2026
Time: 9:00 AM
Venue: Organization Main Office, Hall 2

Please bring your National ID and original documents.

Thank you.
```

### 11.4 Admin Features

Admin can:

- Create exam schedule
- Create interview schedule
- Assign applicants to exam/interview
- Send bulk notifications
- Resend failed notifications
- Track notification delivery status

---

## 12. Admin Dashboard

The dashboard should display statistical data and analytics.

### 12.1 Dashboard Cards

| Card | Description |
|---|---|
| Total Applicants | Number of registered applicants |
| Total Applications | Total submitted applications |
| Open Vacancies | Active job vacancies |
| Closed Vacancies | Closed job vacancies |
| Pending Screening | Applications waiting for review |
| Passed Screening | Applicants who passed initial screening |
| Failed Screening | Applicants who failed screening |
| Shortlisted for Exam | Applicants invited for exam |
| Shortlisted for Interview | Applicants invited for interview |
| Selected Applicants | Final selected candidates |

### 12.2 Charts

Recommended charts:

- Applications per vacancy
- Applications by gender
- Applications by field of study
- Applications by disability status
- Applications by ethnicity, if legally allowed
- Screening pass/fail ratio
- Daily application submissions
- Monthly recruitment trends

### 12.3 Reports

Admin should export reports in:

- Excel
- CSV
- PDF

Recommended reports:

- Vacancy-wise applicant report
- Passed screening report
- Failed screening report
- Exam shortlist report
- Interview shortlist report
- Final selected applicants report
- Document verification report
- Gender/disability/ethnicity summary report
- Audit log report

---

## 13. User Management Module

Admin can manage system users.

### 13.1 User CRUD

Admin can:

- Create user
- View user list
- View user details
- Update user
- Activate/deactivate user
- Delete user, if allowed
- Reset user password
- Assign role
- Assign permissions

### 13.2 User Fields

| Field | Description |
|---|---|
| Full Name | User’s name |
| Email | Login email |
| Phone | Contact phone |
| Role | Assigned role |
| Status | Active/inactive |
| Password | Securely hashed password |
| Created By | Admin who created user |

### 13.3 User Status

| Status | Meaning |
|---|---|
| Active | User can login |
| Inactive | User cannot login |
| Suspended | Temporarily blocked |

---

## 14. Full Role-Based Permission System

The system must be fully role-permission based. Access should not be hard-coded by user type only. Every sensitive action must be protected by a specific permission, and roles should simply be collections of permissions.

### 14.1 Permission-Based Access Model

The system should use the following structure:

```text
User
→ assigned one or more Roles
→ each Role has Permissions
→ each Permission controls a specific action
```

Recommended Laravel package:

```bash
composer require spatie/laravel-permission
```

This package allows the system to manage:

- Roles
- Permissions
- User role assignment
- Permission checks
- Middleware protection
- Policy-based authorization
- Permission syncing

### 14.2 Recommended System Roles

| Role | Description |
|---|---|
| Super Admin | Full access to the entire system without restriction |
| Admin | Manages vacancies, applicants, users, settings, and reports based on assigned permissions |
| HR Manager | Oversees recruitment workflow, screening results, reports, and final decisions |
| HR Officer | Manages vacancies, applications, screening workflow, and notifications |
| Screening Officer | Reviews applicant information and documents and gives screening decisions |
| Document Verifier | Verifies uploaded documents only |
| Exam Officer | Manages exam schedules, shortlisted applicants, and exam results |
| Interview Officer | Manages interview schedules, shortlisted applicants, and interview results |
| Report Viewer | Views dashboards and exports permitted reports |
| Content Manager | Manages landing page, hero slider, organization information, and footer content |
| Applicant | Applies for vacancies and tracks own application status |

### 14.3 Permission Naming Convention

Permissions should follow a consistent pattern:

```text
module.action
```

Examples:

```text
users.view
users.create
users.update
users.delete
vacancies.publish
applications.screen
settings.manage
```

This makes the permission system easy to maintain and easy to use in middleware, policies, and UI menus.

### 14.4 Core Permission List

#### User Management Permissions

| Permission | Description |
|---|---|
| users.view | View system users |
| users.create | Create system users |
| users.update | Update system users |
| users.delete | Delete system users |
| users.activate | Activate users |
| users.deactivate | Deactivate users |
| users.reset-password | Reset user passwords |
| users.assign-role | Assign roles to users |

#### Role and Permission Permissions

| Permission | Description |
|---|---|
| roles.view | View roles |
| roles.create | Create roles |
| roles.update | Update roles |
| roles.delete | Delete roles |
| roles.assign-permissions | Assign permissions to roles |
| permissions.view | View permissions |
| permissions.manage | Manage system permissions |

#### Vacancy Management Permissions

| Permission | Description |
|---|---|
| vacancies.view | View vacancies |
| vacancies.create | Create vacancies |
| vacancies.update | Update vacancies |
| vacancies.delete | Delete vacancies |
| vacancies.publish | Publish vacancies |
| vacancies.close | Close vacancies |
| vacancies.cancel | Cancel vacancies |
| vacancies.archive | Archive vacancies |
| vacancy-documents.manage | Manage required documents per vacancy |
| vacancy-questions.manage | Manage vacancy-specific questions |

#### Application Management Permissions

| Permission | Description |
|---|---|
| applications.view | View submitted applications |
| applications.view-sensitive | View sensitive applicant data such as National ID, ethnicity, and disability status |
| applications.update | Update application records from Admin side |
| applications.delete | Delete or archive applications |
| applications.export | Export application data |
| applications.assign-reviewer | Assign applications to screening officers |
| applications.lock | Lock application editing |
| applications.unlock | Unlock application editing with authorization |

#### Screening Permissions

| Permission | Description |
|---|---|
| screening.view | View screening dashboard |
| screening.review | Review applicant information |
| screening.verify-documents | Verify applicant documents |
| screening.mark-passed | Mark applicant as passed |
| screening.mark-failed | Mark applicant as failed |
| screening.request-correction | Request applicant correction |
| screening.reverse-decision | Reverse screening decision |
| screening.view-history | View screening history |
| screening.export | Export screening reports |

#### Exam and Interview Permissions

| Permission | Description |
|---|---|
| exams.view | View exam schedules |
| exams.create | Create exam schedules |
| exams.update | Update exam schedules |
| exams.delete | Delete exam schedules |
| exams.assign-applicants | Assign applicants to exam |
| exams.record-results | Record exam results |
| interviews.view | View interview schedules |
| interviews.create | Create interview schedules |
| interviews.update | Update interview schedules |
| interviews.delete | Delete interview schedules |
| interviews.assign-applicants | Assign applicants to interview |
| interviews.record-results | Record interview results |

#### Notification Permissions

| Permission | Description |
|---|---|
| notifications.view | View notifications |
| notifications.create | Create notification messages |
| notifications.send | Send notifications |
| notifications.resend | Resend failed notifications |
| notifications.templates.manage | Manage notification templates |

#### Dashboard and Report Permissions

| Permission | Description |
|---|---|
| dashboard.view | View admin dashboard |
| reports.view | View reports |
| reports.export | Export reports |
| reports.applicants | View applicant reports |
| reports.vacancies | View vacancy reports |
| reports.screening | View screening reports |
| reports.exam-interview | View exam/interview reports |
| reports.audit | View audit reports |

#### Landing Page and Content Permissions

| Permission | Description |
|---|---|
| content.view | View public content records |
| content.manage | Manage public website content |
| sliders.view | View hero sliders |
| sliders.create | Create hero sliders |
| sliders.update | Update hero sliders |
| sliders.delete | Delete hero sliders |
| sliders.publish | Publish/unpublish hero sliders |
| footer.manage | Manage footer content |
| organization-info.manage | Manage organization information |

#### System Settings Permissions

| Permission | Description |
|---|---|
| settings.view | View system settings |
| settings.manage | Manage system settings |
| settings.localization | Manage language and translation settings |
| settings.security | Manage security settings |
| settings.notifications | Manage notification settings |
| settings.backup | Manage backup settings |

#### Audit Log Permissions

| Permission | Description |
|---|---|
| audit.view | View audit logs |
| audit.export | Export audit logs |
| audit.delete | Delete audit logs, restricted to Super Admin only |

#### Applicant Permissions

| Permission | Description |
|---|---|
| applicant.profile.view | View own profile |
| applicant.profile.update | Update own profile |
| applicant.vacancies.view | View open vacancies |
| applicant.applications.create | Submit application |
| applicant.applications.view | View own applications |
| applicant.applications.update | Update own application before closing date |
| applicant.documents.upload | Upload own documents |
| applicant.documents.replace | Replace own documents before closing date |
| applicant.notifications.view | View own notifications |
| applicant.status.track | Track own application status |

---

### 14.5 Role-Permission Matrix

| Permission Group | Super Admin | Admin | HR Manager | HR Officer | Screening Officer | Document Verifier | Exam Officer | Interview Officer | Report Viewer | Content Manager | Applicant |
|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| User Management | Yes | Yes | No | No | No | No | No | No | No | No | No |
| Role & Permission Management | Yes | No | No | No | No | No | No | No | No | No | No |
| Vacancy Management | Yes | Yes | Yes | Yes | View only | No | View only | View only | View only | No | View open only |
| Vacancy Publishing | Yes | Yes | Yes | Yes | No | No | No | No | No | No | No |
| Application Viewing | Yes | Yes | Yes | Yes | Assigned only | Assigned only | Assigned only | Assigned only | View only | No | Own only |
| Sensitive Applicant Data | Yes | Yes | Yes | Limited | Limited | Limited | No | No | Limited | No | Own only |
| Document Verification | Yes | Yes | Yes | Yes | Yes | Yes | No | No | No | No | Own upload only |
| Screening Decision | Yes | Yes | Yes | Yes | Yes | No | No | No | No | No | No |
| Reverse Screening Decision | Yes | Yes | Yes | No | No | No | No | No | No | No | No |
| Exam Management | Yes | Yes | Yes | Yes | No | No | Yes | No | View only | No | View own invite |
| Interview Management | Yes | Yes | Yes | Yes | No | No | No | Yes | View only | No | View own invite |
| Notifications | Yes | Yes | Yes | Yes | No | No | Yes | Yes | View only | No | Own only |
| Reports | Yes | Yes | Yes | Yes | No | No | Limited | Limited | Yes | No | No |
| Dashboard | Yes | Yes | Yes | Yes | Limited | Limited | Limited | Limited | Yes | No | Applicant dashboard only |
| Landing Page Content | Yes | Yes | No | No | No | No | No | No | No | Yes | View only |
| System Settings | Yes | Yes | No | No | No | No | No | No | No | No | No |
| Localization Settings | Yes | Yes | No | No | No | No | No | No | No | Content only | No |
| Audit Logs | Yes | View only | View only | No | No | No | No | No | View only | No | No |

---

### 14.6 Menu Visibility Rules

Admin-side menus should be displayed based on permissions.

Examples:

- Show **Users** menu only if the user has `users.view`.
- Show **Vacancies** menu only if the user has `vacancies.view`.
- Show **Screening** menu only if the user has `screening.view`.
- Show **Reports** menu only if the user has `reports.view`.
- Show **Settings** menu only if the user has `settings.view`.

Menu hiding improves user experience, but it is not enough for security. Every route, controller action, policy, and API endpoint must still check permissions.

---

### 14.7 Route Protection Examples

Recommended route structure:

```php
Route::middleware(['auth', 'permission:users.view'])->group(function () {
    Route::get('/admin/users', UserIndexController::class)->name('admin.users.index');
});

Route::middleware(['auth', 'permission:vacancies.create'])->group(function () {
    Route::get('/admin/vacancies/create', VacancyCreateController::class)->name('admin.vacancies.create');
});

Route::middleware(['auth', 'permission:screening.review'])->group(function () {
    Route::get('/admin/screening/{application}', ScreeningShowController::class)->name('admin.screening.show');
});
```

---

### 14.8 Policy Rules

Policies should protect record-level access.

Examples:

- Applicant can only view their own application.
- Applicant can only update an application before the vacancy closing date.
- Screening Officer can only review assigned applications.
- Only authorized roles can view sensitive applicant data.
- Only Super Admin can delete roles or permissions.

Recommended policies:

```text
UserPolicy
VacancyPolicy
ApplicationPolicy
ScreeningPolicy
ApplicationDocumentPolicy
NotificationPolicy
SettingPolicy
ReportPolicy
AuditLogPolicy
```

---

### 14.9 Applicant Access Rules

Applicants must never access Admin routes.

Applicant can only:

- View public vacancies.
- Submit one application per vacancy.
- View own application.
- Edit own application before closing date.
- Upload or replace own documents before closing date.
- View own notifications.
- Track own application status.

Applicant cannot:

- View other applicants.
- View screening dashboard.
- Change screening status.
- Access reports.
- Access system settings.
- Access Admin users.

---

### 14.10 Super Admin Protection

Super Admin role should be protected.

Rules:

- Super Admin cannot be deleted by normal Admin.
- Super Admin role cannot be renamed or removed.
- Permissions cannot be removed from Super Admin accidentally.
- At least one active Super Admin must always exist.
- Only Super Admin can manage roles and permissions.

---

### 14.11 Audit Requirements for Permission Actions

The system must log these actions:

- Role created
- Role updated
- Role deleted
- Permission assigned to role
- Permission removed from role
- User role changed
- User permission changed
- Admin access to sensitive applicant data
- Screening decision changed
- Screening decision reversed

Each log should store:

- User who performed the action
- Action type
- Module
- Old value
- New value
- IP address
- Browser/device information
- Date and time

---

## 15. System Settings

The system should provide a control panel for managing system-wide settings.

### 15.1 General Settings

- Organization name
- Organization logo
- Address
- Phone number
- Email
- Website
- Footer text
- Default language
- Time zone
- Date format

### 15.2 Recruitment Settings

- Default application deadline behavior
- Allow applicant edits before closing date
- Required document types
- Maximum document size: 2 MB
- Allowed file types
- Application reference format
- Enable/disable public vacancy archive
- Enable/disable applicant registration

### 15.3 Notification Settings

- Email sender name
- Email sender address
- SMS gateway settings
- Notification templates
- Exam notification template
- Interview notification template
- Rejection notification template
- Selection notification template

### 15.4 Landing Page Settings

- Header logo
- Hero slider
- Public announcement
- About organization text
- Footer content
- Social media links

### 15.5 Security Settings

- Password policy
- Login attempt limit
- Session timeout
- Two-factor authentication
- Allowed upload file types
- Admin IP restrictions, optional

---

## 16. Full Localization and Multilingual Support

The system must fully support two languages:

| Locale Code | Language |
|---|---|
| en | English |
| am | Amharic |

The default language can be configured from system settings. Applicants and Admin users should be able to switch language from the interface.

---

### 16.1 Localization Objectives

The localization system should allow:

- Public pages to be displayed in English and Amharic.
- Applicant forms to be displayed in English and Amharic.
- Admin menus and labels to be displayed in English and Amharic.
- Validation messages to be translated.
- Notification messages to be sent in the applicant’s preferred language.
- Vacancy announcements to be published in English, Amharic, or both.
- System settings to control default language and available languages.
- Reports to optionally display translated labels.

---

### 16.2 Language Switcher

The system should provide a language switcher on:

- Public landing page
- Vacancy detail page
- Applicant registration page
- Applicant login page
- Applicant dashboard
- Admin dashboard

Example display:

```text
English | አማርኛ
```

When a user selects a language, the system should store the selected locale in:

- Session for guests
- User profile for logged-in users
- Applicant profile for applicants

---

### 16.3 Localized User Interface

All static interface text should be translated.

Examples:

| English | Amharic |
|---|---|
| Login | ግባ |
| Register | ይመዝገቡ |
| Apply Now | አሁን ያመልክቱ |
| Job Vacancies | የስራ ክፍት ቦታዎች |
| Application Status | የማመልከቻ ሁኔታ |
| Full Name | ሙሉ ስም |
| Phone Number | ስልክ ቁጥር |
| Email | ኢሜይል |
| National ID | ብሔራዊ መታወቂያ |
| Submit Application | ማመልከቻ አስገባ |
| Upload Document | ሰነድ ይጫኑ |
| View Details | ዝርዝር ይመልከቱ |
| Track Application | ማመልከቻ ይከታተሉ |
| Passed Screening | የመጀመሪያ ማጣሪያ አልፏል |
| Failed Screening | የመጀመሪያ ማጣሪያ አላለፈም |

---

### 16.4 Localized Validation Messages

Validation messages should be available in both languages.

Example English messages:

```php
return [
    'required' => 'The :attribute field is required.',
    'email' => 'The :attribute must be a valid email address.',
    'max' => [
        'file' => 'The :attribute must not be greater than :max kilobytes.',
    ],
];
```

Example Amharic messages:

```php
return [
    'required' => ':attribute መሙላት ያስፈልጋል።',
    'email' => ':attribute ትክክለኛ የኢሜይል አድራሻ መሆን አለበት።',
    'max' => [
        'file' => ':attribute ከ :max ኪሎባይት መብለጥ የለበትም።',
    ],
];
```

---

### 16.5 Localized Field Names

The system should translate form field names.

Example:

```php
return [
    'full_name' => 'Full Name',
    'phone' => 'Phone Number',
    'email' => 'Email',
    'field_of_study' => 'Field of Study',
    'graduation_date' => 'Graduation Date',
    'national_id' => 'National ID',
    'disability_status' => 'Disability Status',
    'gender' => 'Gender',
    'ethnicity' => 'Ethnicity',
];
```

Amharic version:

```php
return [
    'full_name' => 'ሙሉ ስም',
    'phone' => 'ስልክ ቁጥር',
    'email' => 'ኢሜይል',
    'field_of_study' => 'የትምህርት መስክ',
    'graduation_date' => 'የምረቃ ቀን',
    'national_id' => 'ብሔራዊ መታወቂያ',
    'disability_status' => 'የአካል ጉዳት ሁኔታ',
    'gender' => 'ፆታ',
    'ethnicity' => 'ብሔር',
];
```

---

### 16.6 Localized Vacancy Content

Vacancy announcements should support bilingual content.

Recommended database fields:

| Field | Description |
|---|---|
| title_en | Vacancy title in English |
| title_am | Vacancy title in Amharic |
| description_en | Job description in English |
| description_am | Job description in Amharic |
| requirements_en | Requirements in English |
| requirements_am | Requirements in Amharic |
| location_en | Location in English |
| location_am | Location in Amharic |

Alternative approach:

Use JSON translation columns:

```text
title: {"en": "Software Developer", "am": "ሶፍትዌር ዴቨሎፐር"}
description: {"en": "Job description...", "am": "የስራ መግለጫ..."}
```

Recommended approach for Laravel:

```bash
composer require spatie/laravel-translatable
```

This allows translatable model fields such as:

```php
public array $translatable = [
    'title',
    'description',
    'qualification_requirements',
    'location',
];
```

---

### 16.7 Localized Hero Slider

The hero slider should support English and Amharic content.

Recommended fields:

| Field | Description |
|---|---|
| title_en | Slider title in English |
| title_am | Slider title in Amharic |
| subtitle_en | Slider subtitle in English |
| subtitle_am | Slider subtitle in Amharic |
| button_text_en | Button text in English |
| button_text_am | Button text in Amharic |
| button_link | Shared button link |
| image_path | Slider image |
| status | Active/inactive |
| sort_order | Display order |

---

### 16.8 Localized Notifications

Notification templates should be stored in both languages.

Examples:

| Template | English | Amharic |
|---|---|---|
| Application Submitted | Your application has been submitted successfully. | ማመልከቻዎ በተሳካ ሁኔታ ተልኳል። |
| Passed Screening | You have passed the initial screening. | የመጀመሪያ ማጣሪያውን አልፈዋል። |
| Failed Screening | You did not pass the initial screening. | የመጀመሪያ ማጣሪያውን አላለፉም። |
| Exam Invitation | You are invited for an exam. | ለፈተና ተጠርተዋል። |
| Interview Invitation | You are invited for an interview. | ለቃለ መጠይቅ ተጠርተዋል። |

Applicant notification language should be selected using this priority:

```text
Applicant preferred language
→ User selected language
→ System default language
→ English fallback
```

---

### 16.9 Localized Application Statuses

Application statuses should be translated.

| Status Key | English | Amharic |
|---|---|---|
| submitted | Submitted | ተልኳል |
| under_review | Under Review | በግምገማ ላይ |
| correction_required | Correction Required | ማስተካከያ ያስፈልጋል |
| passed_screening | Passed Initial Screening | የመጀመሪያ ማጣሪያ አልፏል |
| failed_screening | Failed Initial Screening | የመጀመሪያ ማጣሪያ አላለፈም |
| shortlisted_exam | Shortlisted for Exam | ለፈተና ተመርጧል |
| shortlisted_interview | Shortlisted for Interview | ለቃለ መጠይቅ ተመርጧል |
| selected | Selected | ተመርጧል |
| waitlisted | Waitlisted | በተጠባባቂነት ተመዝግቧል |
| not_selected | Not Selected | አልተመረጠም |

---

### 16.10 Localized Admin Menus

Admin menus should also be translated.

| English | Amharic |
|---|---|
| Dashboard | ዳሽቦርድ |
| Users | ተጠቃሚዎች |
| Roles & Permissions | ሚናዎች እና ፈቃዶች |
| Vacancies | የስራ ክፍት ቦታዎች |
| Applications | ማመልከቻዎች |
| Screening | ማጣሪያ |
| Passed Applicants | ያለፉ አመልካቾች |
| Failed Applicants | ያላለፉ አመልካቾች |
| Exam Schedule | የፈተና መርሃ ግብር |
| Interview Schedule | የቃለ መጠይቅ መርሃ ግብር |
| Reports | ሪፖርቶች |
| Settings | ቅንብሮች |
| Audit Logs | የእንቅስቃሴ መዝገቦች |

---

### 16.11 Localization File Structure

Recommended Laravel language file structure:

```text
lang/
├── en/
│   ├── auth.php
│   ├── validation.php
│   ├── fields.php
│   ├── menus.php
│   ├── statuses.php
│   ├── vacancies.php
│   ├── applications.php
│   ├── notifications.php
│   └── settings.php
└── am/
    ├── auth.php
    ├── validation.php
    ├── fields.php
    ├── menus.php
    ├── statuses.php
    ├── vacancies.php
    ├── applications.php
    ├── notifications.php
    └── settings.php
```

---

### 16.12 Locale Middleware

The system should use middleware to set the active language.

Recommended behavior:

```text
Check authenticated user locale
→ otherwise check session locale
→ otherwise use system default locale
→ otherwise fallback to English
```

Example middleware name:

```text
SetLocale
```

Recommended route usage:

```php
Route::middleware(['web', 'set.locale'])->group(function () {
    Route::get('/', HomeController::class)->name('home');
});
```

---

### 16.13 Locale Database Fields

Users and applicants should have preferred language fields.

#### users table

| Column | Type | Description |
|---|---|---|
| preferred_locale | string | en or am |

#### applicants table

| Column | Type | Description |
|---|---|---|
| preferred_locale | string | en or am |

#### settings table

| Key | Example Value |
|---|---|
| app.default_locale | en |
| app.available_locales | ["en", "am"] |
| app.fallback_locale | en |

---

### 16.14 Localization Rules

| Rule | Description |
|---|---|
| English fallback | If Amharic text is missing, English should be displayed |
| Required translations | Public pages, forms, statuses, and notifications must be translated |
| Admin content | Admin should enter vacancy title and description in both languages when required |
| Notification language | Applicant receives notification using preferred language |
| Validation language | Form errors appear in selected language |
| Date format | Dates should follow system setting and selected locale |
| Reports | Reports can use selected system language |

---

### 16.15 Recommended Localization Implementation Steps

1. Create language files for English and Amharic.
2. Add language switcher to public and dashboard layouts.
3. Add `preferred_locale` to users and applicants.
4. Add `SetLocale` middleware.
5. Translate validation messages and form labels.
6. Translate menus and statuses.
7. Make vacancy and slider content translatable.
8. Make notification templates bilingual.
9. Add locale settings in system settings.
10. Test all pages in both English and Amharic.

---

## 17. Improved Features Recommended

The following improvements will make the system more complete, secure, and professional.

### 16.1 Applicant Profile

Applicants should create one profile and reuse it for multiple vacancies.

Benefits:

- Reduces repeated data entry
- Improves applicant experience
- Keeps profile data consistent

### 16.2 Vacancy-Specific Questions

Admin should be able to add custom questions per vacancy.

Example:

- Do you have 2 years of Laravel experience?
- Are you willing to relocate?
- Do you have a professional license?

### 16.3 Eligibility Auto-Check

The system can automatically flag applications that may not meet requirements.

Example checks:

- Wrong field of study
- Missing document
- Graduation date outside allowed range
- Experience below minimum
- Duplicate National ID

The final decision should still be made by authorized staff.

### 16.4 Correction Request

Instead of immediately rejecting an applicant, Admin can request correction before the deadline.

Example:

- Re-upload unreadable document
- Fix wrong graduation date
- Add missing National ID document

### 16.5 Audit Log

Every important action should be logged.

Examples:

- User created
- Vacancy published
- Application submitted
- Application updated
- Document verified
- Screening status changed
- Notification sent
- Settings changed

### 16.6 Data Export

Admin should export applicant lists and reports.

Recommended formats:

- Excel
- CSV
- PDF

### 16.7 Soft Delete

Important records should not be permanently deleted.

Use soft delete for:

- Users
- Vacancies
- Applications
- Documents
- Notifications

### 16.8 Multilingual Support

Recommended languages:

- English
- Amharic

This is especially useful for public vacancy announcements and applicant instructions.

### 16.9 Accessibility

The public applicant interface should support:

- Mobile responsiveness
- Keyboard navigation
- Clear form labels
- High contrast design
- Screen-reader friendly structure

### 16.10 Backup and Recovery

The system should support:

- Daily database backup
- Secure file backup
- Backup retention policy
- Restore procedure

---

## 17. Core Workflows

### 17.1 Vacancy Publication Workflow

```text
Admin logs in
→ Creates vacancy
→ Adds requirements and required documents
→ Saves as Draft
→ Reviews content
→ Publishes vacancy
→ Vacancy appears on public job list
```

### 17.2 Applicant Application Workflow

```text
Applicant opens website
→ Views open vacancies
→ Selects vacancy
→ Registers or logs in
→ Fills application form
→ Uploads required documents
→ Submits application
→ Receives application reference number
→ Tracks status from applicant dashboard
```

### 17.3 Application Update Workflow

```text
Applicant logs in
→ Opens submitted application
→ System checks vacancy closing date
→ If still open, applicant can edit information
→ Applicant updates data/documents
→ System saves changes
→ Audit log records update
```

### 17.4 Screening Workflow

```text
Admin/Screening Officer logs in
→ Opens vacancy applications
→ Reviews applicant information
→ Checks uploaded documents
→ Adds remarks
→ Marks application as Passed, Failed, or Correction Required
→ Applicant status is updated
→ Applicant can track result
```

### 17.5 Exam/Interview Notification Workflow

```text
Admin filters passed applicants
→ Selects applicants for exam/interview
→ Creates schedule
→ Sends notification
→ Applicant receives email/SMS/dashboard alert
→ Notification status is recorded
```

---

## 18. Recommended Database Structure

### 18.1 users

Stores Admin-side users and possibly applicant users depending on design.

| Column | Type |
|---|---|
| id | uuid |
| name | string |
| email | string unique |
| phone | string nullable |
| password | string |
| status | enum |
| email_verified_at | timestamp nullable |
| created_by | uuid nullable |
| timestamps |  |
| softDeletes |  |

---

### 18.2 applicants

Stores applicant profile information.

| Column | Type |
|---|---|
| id | uuid |
| user_id | uuid |
| full_name | string |
| phone | string |
| email | string |
| national_id | string unique |
| gender | enum |
| disability_status | boolean |
| ethnicity | string nullable |
| address | text nullable |
| timestamps |  |

---

### 18.3 vacancies

Stores job vacancy information.

| Column | Type |
|---|---|
| id | uuid |
| title | string |
| code | string unique |
| department | string |
| employment_type | string |
| location | string |
| number_of_positions | integer |
| salary_grade | string nullable |
| description | longText |
| qualification_requirements | longText |
| field_of_study | string nullable |
| minimum_experience | integer nullable |
| opening_date | date |
| closing_date | date |
| status | enum |
| published_at | timestamp nullable |
| created_by | uuid |
| timestamps |  |
| softDeletes |  |

---

### 18.4 vacancy_documents

Defines required documents per vacancy.

| Column | Type |
|---|---|
| id | uuid |
| vacancy_id | uuid |
| document_name | string |
| is_required | boolean |
| allowed_types | json |
| max_size_mb | integer default 2 |
| timestamps |  |

---

### 18.5 applications

Stores applicant applications.

| Column | Type |
|---|---|
| id | uuid |
| applicant_id | uuid |
| vacancy_id | uuid |
| reference_number | string unique |
| field_of_study | string |
| graduation_date | date |
| status | enum |
| submitted_at | timestamp |
| last_updated_at | timestamp nullable |
| locked_at | timestamp nullable |
| screening_status | enum nullable |
| screening_remark | text nullable |
| screened_by | uuid nullable |
| screened_at | timestamp nullable |
| timestamps |  |
| softDeletes |  |

### Unique Constraint

```text
unique(applicant_id, vacancy_id)
```

This prevents one applicant from applying more than once to the same vacancy.

---

### 18.6 application_documents

Stores uploaded applicant documents.

| Column | Type |
|---|---|
| id | uuid |
| application_id | uuid |
| vacancy_document_id | uuid |
| file_name | string |
| original_name | string |
| file_path | string |
| file_type | string |
| file_size | integer |
| verification_status | enum |
| verification_remark | text nullable |
| verified_by | uuid nullable |
| verified_at | timestamp nullable |
| timestamps |  |

---

### 18.7 screening_reviews

Stores detailed screening history.

| Column | Type |
|---|---|
| id | uuid |
| application_id | uuid |
| reviewer_id | uuid |
| previous_status | string nullable |
| new_status | string |
| decision | enum |
| remark | text nullable |
| reviewed_at | timestamp |
| timestamps |  |

---

### 18.8 notifications

Stores notifications sent to applicants.

| Column | Type |
|---|---|
| id | uuid |
| applicant_id | uuid |
| application_id | uuid nullable |
| type | enum |
| channel | enum |
| subject | string nullable |
| message | text |
| status | enum |
| sent_at | timestamp nullable |
| read_at | timestamp nullable |
| timestamps |  |

---

### 18.9 interviews_or_exams

Stores exam/interview schedules.

| Column | Type |
|---|---|
| id | uuid |
| vacancy_id | uuid |
| title | string |
| type | enum: exam/interview |
| date | date |
| start_time | time |
| end_time | time nullable |
| venue | string |
| instruction | text nullable |
| created_by | uuid |
| timestamps |  |

---

### 18.10 interview_exam_applicants

Stores assigned applicants for exam/interview.

| Column | Type |
|---|---|
| id | uuid |
| schedule_id | uuid |
| application_id | uuid |
| status | enum |
| score | decimal nullable |
| remark | text nullable |
| timestamps |  |

---

### 18.11 hero_sliders

Stores landing page slider data.

| Column | Type |
|---|---|
| id | uuid |
| title | string |
| subtitle | text nullable |
| image_path | string |
| button_text | string nullable |
| button_link | string nullable |
| status | boolean |
| sort_order | integer |
| timestamps |  |

---

### 18.12 settings

Stores system-wide settings.

| Column | Type |
|---|---|
| id | uuid |
| key | string unique |
| value | longText nullable |
| type | string |
| group | string nullable |
| timestamps |  |

---

### 18.13 audit_logs

Stores system activity logs.

| Column | Type |
|---|---|
| id | uuid |
| user_id | uuid nullable |
| action | string |
| module | string |
| record_id | uuid nullable |
| old_values | json nullable |
| new_values | json nullable |
| ip_address | string nullable |
| user_agent | text nullable |
| created_at | timestamp |

---

## 19. Validation Requirements

### 19.1 Applicant Form Validation

| Field | Rule |
|---|---|
| Full Name | Required, string, max 255 |
| Phone Number | Required, valid format |
| Email | Required, email |
| Field of Study | Required |
| Graduation Date | Required, valid date |
| National ID | Required, unique |
| Disability Status | Required |
| Gender | Required |
| Ethnicity | Optional or required depending on policy |
| Documents | Required based on vacancy setting |
| File Size | Maximum 2 MB |
| File Type | PDF/JPG/JPEG/PNG only |

### 19.2 Vacancy Validation

| Field | Rule |
|---|---|
| Job Title | Required |
| Job Code | Required, unique |
| Opening Date | Required |
| Closing Date | Required, must be after opening date |
| Description | Required |
| Qualification | Required |
| Number of Positions | Required, numeric, minimum 1 |
| Status | Required |

---

## 20. Security Requirements

### 20.1 Authentication

- Secure login for Admin and Applicants.
- Password hashing.
- Password reset.
- Email verification recommended.
- Optional two-factor authentication for Admin users.

### 20.2 Authorization

- Role-based access control.
- Permission checks on every sensitive action.
- Applicants cannot access other applicants’ data.
- Screening Officers cannot modify system settings.

### 20.3 File Security

- Uploaded files must be stored in private storage.
- Files should not be directly accessible through public URLs.
- File names should be randomly generated.
- File type and size must be validated.
- Maximum file size must be enforced at both frontend and backend.
- Admin document previews should require authentication.

### 20.4 Data Privacy

Sensitive applicant data must be protected, especially:

- National ID
- Disability status
- Ethnicity
- Contact information
- Uploaded documents

Recommended controls:

- Limit access by role.
- Log every access and update.
- Avoid exposing sensitive fields in exports unless needed.
- Use HTTPS.
- Encrypt backups.
- Apply retention policy after recruitment completion.

---

## 21. Non-Functional Requirements

### 21.1 Performance

- Public vacancy list should load quickly.
- Dashboard statistics should use optimized queries.
- Large applicant lists should use pagination.
- Reports should run through background jobs for large data.

### 21.2 Availability

- System should be available during application periods.
- Regular backups should be scheduled.
- Error pages should be user-friendly.

### 21.3 Scalability

The system should support:

- Many vacancies
- Thousands of applicants
- Large document storage
- Multiple screening officers
- Concurrent application submissions

### 21.4 Usability

- Mobile-friendly applicant interface.
- Clear form instructions.
- Progress indicator during application.
- Clear error messages.
- Applicant should know which documents are missing.

### 21.5 Maintainability

- Clean modular structure.
- Separate business logic from controllers.
- Use Form Requests for validation.
- Use Policies/Gates for authorization.
- Use Queues for notifications and report generation.
- Use Activity Logs for traceability.

---

## 22. Recommended Laravel 12 Implementation Approach

### 22.1 Suggested Stack

| Layer | Recommendation |
|---|---|
| Backend | Laravel 12 |
| Frontend | Blade + Tailwind, or Inertia 2.0 with React/Vue |
| Authentication | Laravel Breeze, Jetstream, or custom auth |
| Authorization | Policies/Gates or Spatie Laravel Permission |
| Database | MySQL or PostgreSQL |
| File Storage | Laravel Storage private disk |
| Notifications | Laravel Notifications + Queue |
| Reports | Laravel Excel / PDF package |
| Testing | Pest |
| UI | Tailwind CSS |
| Admin Panel | Custom admin panel or Filament |

For faster development, **Filament** is a strong choice for the Admin side because CRUD, dashboards, filters, forms, roles, and resource management can be built quickly.

For a custom public applicant portal, use Laravel controllers, Form Requests, Actions, and Eloquent Resources where APIs are needed.

### 22.2 Recommended Architecture

```text
app/
├── Actions/
│   ├── Applications/
│   ├── Vacancies/
│   └── Screening/
├── Enums/
├── Http/
│   ├── Controllers/
│   ├── Requests/
│   └── Resources/
├── Models/
├── Notifications/
├── Policies/
├── Jobs/
└── Services/
```

Use:

- **Actions** for business logic
- **Form Requests** for validation
- **Policies** for authorization
- **Enums** for statuses
- **Jobs** for background notifications/reports
- **Notifications** for email/SMS/dashboard alerts
- **Observers** for audit logging where appropriate

---

## 23. Recommended Pages

### 23.1 Public Pages

| Page | Purpose |
|---|---|
| Home Page | Landing page with slider and vacancy list |
| Vacancy List | Shows all open vacancies |
| Vacancy Detail | Shows full job description and requirements |
| Applicant Register | Applicant account registration |
| Applicant Login | Applicant login |
| Application Form | Submit job application |
| Track Application | Check application status |
| Contact Page | Organization contact information |

### 23.2 Applicant Dashboard Pages

| Page | Purpose |
|---|---|
| Dashboard | Summary of applicant applications |
| My Profile | Applicant personal information |
| My Applications | Submitted applications |
| Edit Application | Update application before closing date |
| Documents | Manage uploaded documents |
| Notifications | View exam/interview alerts |
| Status Tracking | View current application progress |

### 23.3 Admin Pages

| Page | Purpose |
|---|---|
| Dashboard | Statistics and analytics |
| Users | Manage system users |
| Roles & Permissions | Manage access control |
| Vacancies | Manage job vacancies |
| Applications | View all applications |
| Screening | Review and verify applicants |
| Passed Applicants | View applicants who passed screening |
| Failed Applicants | View applicants who failed screening |
| Exam/Interview Schedule | Manage exam/interview calls |
| Notifications | Send and view notifications |
| Reports | Generate reports |
| Hero Slider | Manage landing page slider |
| Settings | Manage system settings |
| Audit Logs | View system activity |

---

## 24. Applicant Status Flow

```text
Submitted
→ Under Review
→ Correction Required
→ Updated by Applicant
→ Under Review
→ Passed Initial Screening
→ Shortlisted for Exam
→ Exam Completed
→ Shortlisted for Interview
→ Interview Completed
→ Selected / Waitlisted / Not Selected
```

Alternative failed path:

```text
Submitted
→ Under Review
→ Failed Initial Screening
→ Not Selected
```

---

## 25. Admin Screening Flow

```text
Pending Screening
→ Review applicant information
→ Review documents
→ Verify eligibility
→ Add remarks
→ Decision:
   - Passed
   - Failed
   - Correction Required
```

---

## 26. Reporting Requirements

### 26.1 Vacancy Report

Should include:

- Vacancy title
- Vacancy code
- Opening date
- Closing date
- Number of applicants
- Number passed
- Number failed
- Number shortlisted
- Number selected

### 26.2 Applicant Report

Should include:

- Full name
- Phone
- Email
- National ID
- Vacancy applied for
- Field of study
- Graduation date
- Gender
- Disability status
- Application status
- Screening status
- Remarks

### 26.3 Screening Report

Should include:

- Applicant name
- Vacancy
- Reviewer
- Decision
- Screening date
- Remarks
- Document verification status

### 26.4 Notification Report

Should include:

- Applicant name
- Vacancy
- Notification type
- Channel
- Status
- Sent date

---

## 27. Business Rules Summary

| Rule | Description |
|---|---|
| One application per vacancy | Applicant cannot apply more than once to the same vacancy |
| Edit before deadline only | Applicant can update application until vacancy closing date |
| Lock after deadline | Application becomes read-only after vacancy closes |
| Required documents mandatory | Applicant cannot submit without required documents |
| Max upload size | Each uploaded file must not exceed 2 MB |
| Admin review required | Screening decision must be made by authorized user |
| Screening remark required | Failed applications should include reason |
| Status tracking | Applicant must be able to view current status |
| Role-based access | Every admin action must be permission-controlled |
| Audit logging | Sensitive actions must be logged |
| Private documents | Uploaded files must not be publicly accessible |

---

## 28. Recommended Development Phases

### Phase 1: Core Setup

- Authentication
- Roles and permissions
- Admin layout
- Applicant layout
- System settings
- User management

### Phase 2: Vacancy and Landing Page

- Vacancy CRUD
- Public vacancy list
- Vacancy detail page
- Hero slider management
- Footer/header settings

### Phase 3: Applicant Application

- Applicant registration/login
- Applicant profile
- Application form
- Document upload
- Duplicate application prevention
- Application update before closing date
- Application tracking

### Phase 4: Screening

- Screening dashboard
- Applicant filtering
- Document verification
- Screening status
- Passed/failed applicant lists
- Screening history

### Phase 5: Notifications and Reports

- Exam/interview schedule
- Email/SMS/in-system notifications
- Export reports
- Dashboard analytics
- Audit logs

### Phase 6: Testing and Deployment

- Feature tests
- Authorization tests
- Upload validation tests
- Duplicate application tests
- Security review
- Production deployment
- Backup configuration

---

## 29. Testing Requirements

### 29.1 Important Test Cases

| Test Case | Expected Result |
|---|---|
| Applicant applies to open vacancy | Application submitted successfully |
| Applicant applies twice to same vacancy | System blocks duplicate application |
| Applicant edits before closing date | Update allowed |
| Applicant edits after closing date | Update blocked |
| Applicant uploads file over 2 MB | Upload rejected |
| Applicant misses required document | Submission blocked |
| Screening Officer marks applicant passed | Applicant appears in passed list |
| Screening Officer marks applicant failed | Applicant appears in failed list |
| Unauthorized user accesses settings | Access denied |
| Admin sends exam notification | Applicant receives notification |
| Applicant tracks application | Correct status displayed |

---

## 30. Final Recommendation

Build the system as a modular Laravel 12 application with a clean separation between Admin and Applicant areas.

Recommended route structure:

```text
/admin
/applicant
/
```

Use:

- **Policies** for role-based access
- **Form Requests** for validation
- **Actions** for application submission, screening, and notification logic
- **Enums** for statuses
- **Private file storage** for documents
- **Queues** for notifications and reports
- **Audit logs** for accountability
- **Soft deletes** for important records
- **UUIDv7** for primary keys

The most important business rules to protect are:

1. No duplicate application for the same vacancy.
2. Applicant can update information only before the closing date.
3. Uploaded documents must be limited to 2 MB.
4. Screening decisions must be traceable.
5. Sensitive applicant data must be protected by role-based permissions.

