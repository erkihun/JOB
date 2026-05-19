<?php

return [
    'application_submitted' => [
        'subject' => 'Application Received – :vacancy',
        'greeting' => 'Dear :name,',
        'body' => 'Your application for :vacancy has been received. Your reference number is :reference.',
        'closing' => 'We will review your application and contact you with updates.',
    ],
    'correction_required' => [
        'subject' => 'Correction Required – :vacancy',
        'greeting' => 'Dear :name,',
        'body' => 'Your application for :vacancy requires corrections. Please log in and update your application.',
        'remark' => 'Reviewer note: :remark',
        'closing' => 'Please make the required corrections before the deadline.',
    ],
    'screening_passed' => [
        'subject' => 'Application Passed Screening – :vacancy',
        'greeting' => 'Dear :name,',
        'body' => 'Congratulations! Your application for :vacancy has passed the initial screening.',
        'closing' => 'You will be contacted with further instructions.',
    ],
    'screening_failed' => [
        'subject' => 'Application Outcome – :vacancy',
        'greeting' => 'Dear :name,',
        'body' => 'Thank you for applying for :vacancy. After careful review, your application did not meet the requirements.',
        'closing' => 'We encourage you to apply for future opportunities.',
    ],
    'exam_invitation' => [
        'subject' => 'Exam Invitation – :vacancy',
        'greeting' => 'Dear :name,',
        'body' => 'You are invited to sit an examination for :vacancy.',
        'details' => 'Date: :date | Time: :time | Venue: :venue',
        'instructions' => 'Instructions: :instructions',
        'closing' => 'Please arrive 15 minutes early with a valid ID.',
    ],
    'interview_invitation' => [
        'subject' => 'Interview Invitation – :vacancy',
        'greeting' => 'Dear :name,',
        'body' => 'You are invited to attend an interview for :vacancy.',
        'details' => 'Date: :date | Time: :time | Venue: :venue',
        'instructions' => 'Instructions: :instructions',
        'closing' => 'Please confirm your attendance by contacting us.',
    ],
    'selected' => [
        'subject' => 'Congratulations – Selected for :vacancy',
        'greeting' => 'Dear :name,',
        'body' => 'We are pleased to inform you that you have been selected for :vacancy.',
        'closing' => 'Our HR team will contact you with onboarding details.',
    ],
    'waitlisted' => [
        'subject' => 'Application Status – :vacancy',
        'greeting' => 'Dear :name,',
        'body' => 'You have been placed on the waitlist for :vacancy.',
        'closing' => 'We will contact you if a position becomes available.',
    ],
    'not_selected' => [
        'subject' => 'Application Outcome – :vacancy',
        'greeting' => 'Dear :name,',
        'body' => 'Thank you for your interest in :vacancy. After careful consideration, we have selected other candidates.',
        'closing' => 'We wish you success in your future endeavors.',
    ],
];
