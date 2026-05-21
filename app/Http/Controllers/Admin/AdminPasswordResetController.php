<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PasswordResetOtp;
use App\Models\User;
use App\Notifications\PasswordResetOtpNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminPasswordResetController extends Controller
{
    public function showForgotForm(): View
    {
        return view('admin.auth.forgot-password');
    }

    public function sendOtp(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $user = User::where('email', $request->email)->first();

        // Only send for admin-type users; success message shown regardless to prevent enumeration
        if ($user && ! $user->hasRole('applicant') && $user->roles->isNotEmpty()) {
            $otp = (string) random_int(100000, 999999);

            PasswordResetOtp::where('email', $request->email)->delete();

            PasswordResetOtp::create([
                'email'      => $request->email,
                'otp'        => Hash::make($otp),
                'expires_at' => now()->addMinutes(10),
            ]);

            $user->notify(new PasswordResetOtpNotification($otp));
        }

        session(['admin_password_reset_email' => $request->email]);

        return redirect()->route('admin.password.otp')
            ->with('info', __('auth.otp_sent'));
    }

    public function showOtpForm(): View|RedirectResponse
    {
        if (! session('admin_password_reset_email')) {
            return redirect()->route('admin.password.request');
        }

        return view('admin.auth.verify-otp');
    }

    public function verifyOtp(Request $request): RedirectResponse
    {
        $request->validate(['otp' => ['required', 'digits:6']]);

        $email = session('admin_password_reset_email');

        if (! $email) {
            return redirect()->route('admin.password.request');
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
            'admin_password_reset_email' => $email,
            'admin_password_reset_token' => $token,
        ]);

        return redirect()->route('admin.password.reset.show');
    }

    public function showResetForm(): View|RedirectResponse
    {
        if (! session('admin_password_reset_email') || ! session('admin_password_reset_token')) {
            return redirect()->route('admin.password.request');
        }

        return view('admin.auth.reset-password', [
            'token' => session('admin_password_reset_token'),
            'email' => session('admin_password_reset_email'),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token'                 => ['required'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required'],
        ]);

        $email = session('admin_password_reset_email');
        $token = session('admin_password_reset_token');

        if (! $email || ! $token || ! hash_equals($token, $request->token)) {
            return redirect()->route('admin.password.request')
                ->withErrors(['email' => __('auth.reset_expired')]);
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            return redirect()->route('admin.password.request');
        }

        $user->forceFill(['password' => Hash::make($request->password)])->save();

        session()->forget(['admin_password_reset_email', 'admin_password_reset_token']);

        return redirect()->route('admin.login')
            ->with('success', __('auth.password_reset_success'));
    }
}
