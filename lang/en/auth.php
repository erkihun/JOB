<?php

return [
    'failed' => 'These credentials do not match our records.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',
    'registered_successfully' => 'Your account has been created successfully.',
    'not_an_applicant' => 'This login is for applicants only. Please use the admin panel.',
    'account_inactive' => 'Your account is inactive. Please contact support.',

    // Password reset via OTP
    'forgot_password'       => 'Forgot password',
    'forgot_password_hint'  => 'Enter your email and we\'ll send a 6-digit code to reset your password.',
    'send_otp'              => 'Send Reset Code',
    'verify_otp'            => 'Enter Reset Code',
    'verify_otp_button'     => 'Verify Code',
    'otp_code'              => '6-Digit Code',
    'otp_sent_to'           => 'We sent a code to :email. Check your inbox.',
    'otp_expires_hint'      => 'This code expires in 10 minutes.',
    'resend_otp'            => 'Didn\'t receive it? Resend code',
    'back_to_login'         => 'Back to login',
    'reset_password'        => 'Set New Password',
    'reset_password_hint'   => 'Choose a strong password for your account.',
    'new_password'          => 'New Password',
    'confirm_password'      => 'Confirm Password',
    'reset_password_button' => 'Save New Password',
    'otp_invalid'           => 'The code is invalid or has expired. Please try again.',
    'reset_expired'         => 'Your reset session has expired. Please start over.',
    'password_reset_success'=> 'Password reset successfully. You can now log in.',

    // OTP email content
    'otp_subject'   => 'Your password reset code — :org',
    'otp_greeting'  => 'Hello :name,',
    'otp_line1'     => 'You requested a password reset. Use the code below:',
    'otp_expires'   => 'This code expires in 10 minutes.',
    'otp_ignore'    => 'If you did not request this, you can safely ignore this email.',
    'otp_sent'      => 'If your email is registered, a reset code has been sent. Please check your inbox.',

    // Email verification on registration
    'verify_email'          => 'Verify Your Email',
    'verify_email_hint'     => 'We sent a 6-digit code to your email address. Enter it below to verify your account.',
    'email_verified_success' => 'Email verified successfully. Welcome aboard!',
    'otp_resent'            => 'A new verification code has been sent to your email.',
];
