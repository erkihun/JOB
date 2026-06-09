<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\RegisterApplicantAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ApplicantRegisterRequest;
use App\Models\PasswordResetOtp;
use App\Models\Setting;
use App\Notifications\PasswordResetOtpNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicantAuthController extends Controller
{
    public function showRegister(): View
    {
        abort_unless((bool) Setting::get('recruitment.allow_registration', true), 403);

        return view('applicant.auth.register');
    }

    public function register(ApplicantRegisterRequest $request, RegisterApplicantAction $action): RedirectResponse
    {
        abort_unless((bool) Setting::get('recruitment.allow_registration', true), 403);

        $data = $request->validated();

        // Recover valid documents stored during a previous failed submission.
        if (! $request->hasFile('documents') && Session::has('reg_temp_docs')) {
            $abs = storage_path('app/'.(string) Session::get('reg_temp_docs'));
            if (file_exists($abs)) {
                $data['documents'] = new UploadedFile(
                    $abs,
                    (string) Session::get('reg_temp_docs_name', 'documents.pdf'),
                    'application/pdf',
                    null,
                    true
                );
            }
        }

        $user = $action->handle($data);

        // Clean up temp files
        if (Session::has('reg_temp_docs')) {
            Storage::disk('local')->delete((string) Session::pull('reg_temp_docs'));
        }
        Session::forget('reg_temp_photo');
        Session::forget('reg_temp_docs_name');

        Auth::login($user);

        // Send email verification OTP
        PasswordResetOtp::where('email', $user->email)->delete();
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        PasswordResetOtp::create([
            'email' => $user->email,
            'otp' => Hash::make($otp),
            'expires_at' => now()->addMinutes(10),
        ]);
        $user->notify(new PasswordResetOtpNotification($otp));

        return redirect()->route('applicant.verify-email');
    }

    public function tempPhoto(): StreamedResponse
    {
        $path = (string) Session::get('reg_temp_photo', '');
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        $ext = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'png' => 'image/png',
            default => 'image/jpeg',
        };

        return response()->stream(function () use ($path): void {
            $stream = Storage::disk('local')->readStream($path);
            if ($stream !== false) {
                fpassthru($stream);
            }
        }, 200, ['Content-Type' => $mime, 'Cache-Control' => 'no-store']);
    }

    public function validateField(Request $request): JsonResponse
    {
        $field = $request->query('field', '');
        $value = (string) $request->query('value', '');
        $allowed = ['email', 'phone', 'national_id'];

        if (! in_array($field, $allowed, true) || $value === '') {
            return response()->json(['valid' => true]);
        }

        if ($field === 'national_id') {
            $value = preg_replace('/\D/', '', $value) ?: $value;
        }

        if ($field === 'phone') {
            $value = $this->normalizeEthiopianPhone($value);
        }

        $checks = [
            'email' => [['users', 'email', true], ['applicants', 'email', false]],
            'phone' => [['users', 'phone', true], ['applicants', 'phone', false]],
            'national_id' => [['applicants', 'national_id', false]],
        ];

        foreach ($checks[$field] as [$table, $column, $softDelete]) {
            $q = DB::table($table)->where($column, $value);
            if ($softDelete) {
                $q->whereNull('deleted_at');
            }
            if ($q->exists()) {
                return response()->json([
                    'valid' => false,
                    'message' => __("validation.{$field}_taken"),
                ]);
            }
        }

        return response()->json(['valid' => true]);
    }

    private function normalizeEthiopianPhone(string $value): string
    {
        $digits = preg_replace('/\D/', '', $value);

        if (preg_match('/^09\d{8}$/', (string) $digits) === 1) {
            return '+251'.substr((string) $digits, 1);
        }

        if (preg_match('/^2519\d{8}$/', (string) $digits) === 1) {
            return '+'.$digits;
        }

        if (preg_match('/^9\d{8}$/', (string) $digits) === 1) {
            return '+251'.$digits;
        }

        return trim($value);
    }

    public function showLogin(): View
    {
        return view('applicant.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => __('auth.failed'),
            ])->onlyInput('email');
        }

        $user = Auth::user();

        // Block admin users from applicant area
        if (! $user->hasRole('applicant')) {
            Auth::logout();

            return back()->withErrors([
                'email' => __('auth.not_an_applicant'),
            ]);
        }

        if (! $user->isActive()) {
            Auth::logout();

            return back()->withErrors([
                'email' => __('auth.account_inactive'),
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('applicant.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
