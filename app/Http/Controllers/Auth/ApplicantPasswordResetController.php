<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PasswordResetOtp;
use App\Models\User;
use App\Notifications\PasswordResetOtpNotification;
use App\Services\Security\PasswordPolicyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ApplicantPasswordResetController extends Controller
{
    public function showForgotForm(): View
    {
        return view('applicant.auth.forgot-password');
    }

    public function sendOtp(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $user = User::where('email', $request->email)->first();

        // Only send for applicant users; success message shown regardless to prevent enumeration
        if ($user && $user->hasRole('applicant')) {
            $otp = (string) random_int(100000, 999999);

            PasswordResetOtp::where('email', $request->email)->delete();

            PasswordResetOtp::create([
                'email' => $request->email,
                'otp' => Hash::make($otp),
                'expires_at' => now()->addMinutes(10),
            ]);

            $user->notify(new PasswordResetOtpNotification($otp));
        }

        session(['applicant_password_reset_email' => $request->email]);

        return redirect()->route('applicant.password.otp')
            ->with('info', __('auth.otp_sent'));
    }

    public function showOtpForm(): View|RedirectResponse
    {
        if (! session('applicant_password_reset_email')) {
            return redirect()->route('applicant.password.request');
        }

        return view('applicant.auth.verify-otp');
    }

    public function verifyOtp(Request $request): RedirectResponse
    {
        $request->validate(['otp' => ['required', 'digits:6']]);

        $email = session('applicant_password_reset_email');

        if (! $email) {
            return redirect()->route('applicant.password.request');
        }

        $record = PasswordResetOtp::where('email', $email)
            ->orderByDesc('created_at')
            ->first();

        if (! $record || $record->isExpired() || ! Hash::check($request->otp, $record->otp)) {
            return back()->withErrors(['otp' => __('auth.otp_invalid')]);
        }

        $record->delete();

        $token = Str::random(40);
        session([
            'applicant_password_reset_email' => $email,
            'applicant_password_reset_token' => $token,
        ]);

        return redirect()->route('applicant.password.reset.show');
    }

    public function showResetForm(): View|RedirectResponse
    {
        if (! session('applicant_password_reset_email') || ! session('applicant_password_reset_token')) {
            return redirect()->route('applicant.password.request');
        }

        return view('applicant.auth.reset-password', [
            'token' => session('applicant_password_reset_token'),
            'email' => session('applicant_password_reset_email'),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'password' => ['required', 'confirmed', ...app(PasswordPolicyService::class)->applicantRules()],
            'password_confirmation' => ['required'],
        ]);

        $email = session('applicant_password_reset_email');
        $token = session('applicant_password_reset_token');

        if (! $email || ! $token || ! hash_equals($token, $request->token)) {
            return redirect()->route('applicant.password.request')
                ->withErrors(['email' => __('auth.reset_expired')]);
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            return redirect()->route('applicant.password.request');
        }

        $user->forceFill(['password' => Hash::make($request->password)])->save();

        session()->forget(['applicant_password_reset_email', 'applicant_password_reset_token']);

        return redirect()->route('login')
            ->with('success', __('auth.password_reset_success'));
    }
}
