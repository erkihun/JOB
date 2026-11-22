<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\PasswordResetOtpNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function unverifiedApplicant(): User
{
    $user = User::factory()->unverified()->create(['name' => 'Selam Haile']);
    $user->assignRole('applicant');

    return $user;
}

test('verification email uses verification wording, not password reset', function (): void {
    $user = unverifiedApplicant();

    $mail = (new PasswordResetOtpNotification('123456', PasswordResetOtpNotification::PURPOSE_VERIFY))->toMail($user);
    $html = (string) $mail->render();

    expect($mail->subject)->toContain('verification')
        ->and($html)->toContain('123456')
        ->and($html)->toContain('Selam Haile')
        ->and($html)->toContain(__('auth.verify_otp_line1'))
        ->and($html)->not->toContain('safely ignore')
        ->and($html)->not->toContain('password reset');
});

test('password reset email keeps reset wording', function (): void {
    $user = unverifiedApplicant();

    $mail = (new PasswordResetOtpNotification('654321'))->toMail($user);

    expect($mail->subject)->toContain('password reset')
        ->and((string) $mail->render())->toContain(__('auth.otp_line1'));
});

test('resending the verification code sends a verification email', function (): void {
    Notification::fake();
    $user = unverifiedApplicant();

    $this->actingAs($user)->post(route('applicant.verify-email.resend'))
        ->assertSessionHas('info');

    Notification::assertSentTo($user, PasswordResetOtpNotification::class, function ($n) use ($user) {
        return str_contains($n->toMail($user)->subject, 'verification');
    });
});

test('a mail server failure on resend shows an error instead of crashing', function (): void {
    $user = unverifiedApplicant();
    config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '127.0.0.1', 'mail.mailers.smtp.port' => 1, 'mail.mailers.smtp.timeout' => 2]);

    $this->actingAs($user)->from(route('applicant.verify-email'))
        ->post(route('applicant.verify-email.resend'))
        ->assertRedirect(route('applicant.verify-email'))
        ->assertSessionHasErrors(['otp' => __('auth.otp_send_failed')]);
});

test('a mail server failure on password reset keeps the generic response', function (): void {
    $user = unverifiedApplicant();
    config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '127.0.0.1', 'mail.mailers.smtp.port' => 1, 'mail.mailers.smtp.timeout' => 2]);

    $this->post(route('applicant.password.email'), ['email' => $user->email])
        ->assertRedirect(route('applicant.password.otp'))
        ->assertSessionHas('info', __('auth.otp_sent'));
});

test('password reset delivers the code without a queue worker', function (string $route): void {
    Queue::fake();
    config(['mail.default' => 'array']);
    $user = $route === 'admin.password.email' ? User::factory()->admin()->create() : unverifiedApplicant();

    $this->post(route($route), ['email' => $user->email])->assertSessionHas('info');

    $messages = Mail::mailer('array')->getSymfonyTransport()->messages();
    expect($messages)->toHaveCount(1)
        ->and($messages->first()->getOriginalMessage()->getTo()[0]->getAddress())->toBe($user->email);
})->with(['applicant.password.email', 'admin.password.email']);

test('staff can request a reset code from the shared forgot-password page', function (): void {
    Notification::fake();
    $admin = User::factory()->admin()->create();

    $this->post(route('applicant.password.email'), ['email' => $admin->email])
        ->assertRedirect(route('applicant.password.otp'))
        ->assertSessionHas('info', __('auth.otp_sent'));

    Notification::assertSentTo($admin, PasswordResetOtpNotification::class);
});

test('unknown emails get the same response and no email', function (): void {
    Notification::fake();

    $this->post(route('applicant.password.email'), ['email' => 'nobody@example.com'])
        ->assertRedirect(route('applicant.password.otp'))
        ->assertSessionHas('info', __('auth.otp_sent'));

    Notification::assertNothingSent();
});

test('staff resetting via the shared page must meet the staff password policy', function (): void {
    $admin = User::factory()->admin()->create();
    session(['applicant_password_reset_email' => $admin->email, 'applicant_password_reset_token' => 'tok']);

    $this->post(route('applicant.password.reset'), [
        'token' => 'tok', 'password' => 'short', 'password_confirmation' => 'short',
    ])->assertSessionHasErrors('password');

    $this->post(route('applicant.password.reset'), [
        'token' => 'tok', 'password' => 'Very$trongAdmin2026x', 'password_confirmation' => 'Very$trongAdmin2026x',
    ])->assertRedirect(route('login'));

    expect(Hash::check('Very$trongAdmin2026x', $admin->fresh()->password))->toBeTrue();
});
