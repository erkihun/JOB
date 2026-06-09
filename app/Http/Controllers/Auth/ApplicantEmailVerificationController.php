<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PasswordResetOtp;
use App\Notifications\PasswordResetOtpNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ApplicantEmailVerificationController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if ($request->user()->email_verified_at !== null) {
            return redirect()->route('applicant.dashboard');
        }

        return view('applicant.auth.verify-email');
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate(['otp' => ['required', 'digits:6']]);

        $user = $request->user();

        if ($user->email_verified_at !== null) {
            return redirect()->route('applicant.dashboard');
        }

        $record = PasswordResetOtp::where('email', $user->email)
            ->orderByDesc('created_at')
            ->first();

        if (! $record || $record->isExpired() || ! Hash::check($request->input('otp'), $record->otp)) {
            return back()->withErrors(['otp' => __('auth.otp_invalid')]);
        }

        $record->delete();

        $user->update(['email_verified_at' => now()]);

        return redirect()->route('applicant.dashboard')
            ->with('success', __('auth.email_verified_success'));
    }

    public function resend(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->email_verified_at !== null) {
            return redirect()->route('applicant.dashboard');
        }

        PasswordResetOtp::where('email', $user->email)->delete();

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        PasswordResetOtp::create([
            'email' => $user->email,
            'otp' => Hash::make($otp),
            'expires_at' => now()->addMinutes(10),
        ]);
        $user->notify(new PasswordResetOtpNotification($otp));

        return back()->with('info', __('auth.otp_resent'));
    }
}
