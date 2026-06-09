<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\Security\MfaSettings;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use PragmaRX\Google2FA\Google2FA;
use PragmaRX\Google2FALaravel\Support\Authenticator;

final class MfaController extends Controller
{
    public function __construct(
        private readonly Google2FA $google2fa,
        private readonly MfaSettings $settings,
    ) {}

    public function show(Request $request): View|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $this->settings->enabled()) {
            return redirect()->to($this->destinationFor($user));
        }

        if ($user->hasTwoFactorEnabled()) {
            if (! session(config('google2fa.session_var').'.auth_passed') && ! $user->hasRememberedMfaDevice($request)) {
                return redirect()->route('mfa.challenge');
            }

            return view('auth.mfa.manage', [
                'enabled' => true,
                'recoveryCodes' => $request->session()->pull('mfa_plain_recovery_codes', []),
            ]);
        }

        if (! $request->session()->has('mfa_setup_secret')) {
            $request->session()->put('mfa_setup_secret', $this->google2fa->generateSecretKey());
        }

        $secret = (string) $request->session()->get('mfa_setup_secret');
        $qrCodeUrl = $this->google2fa->getQRCodeUrl($this->settings->issuerName(), $user->email, $secret);

        return view('auth.mfa.manage', [
            'enabled' => false,
            'secret' => $secret,
            'qrCodeSvg' => $this->renderSvg($qrCodeUrl),
            'recoveryCodes' => [],
        ]);
    }

    public function enable(Request $request): RedirectResponse
    {
        $request->validate(['one_time_password' => ['required', 'digits:6']]);

        /** @var User $user */
        $user = Auth::user();
        $secret = (string) $request->session()->get(
            'mfa_setup_secret',
            $request->session()->get('2fa_setup_secret', ''),
        );

        if ($secret === '' || ! $this->google2fa->verifyKey($secret, $request->input('one_time_password'))) {
            return back()
                ->withErrors(['one_time_password' => __('auth.mfa_invalid_code')])
                ->withInput();
        }

        $plainCodes = $this->generateRecoveryCodes();

        $user->forceFill([
            'google2fa_secret' => $secret,
            'google2fa_recovery_codes' => array_map(fn (string $code): string => Hash::make($code), $plainCodes),
        ])->save();

        $request->session()->forget(['mfa_setup_secret', '2fa_setup_secret']);
        $request->session()->put('mfa_plain_recovery_codes', $plainCodes);
        app(Authenticator::class)->boot($request)->login();

        AuditLog::record('mfa_enabled', 'auth', (string) $user->id);

        return redirect()->route('mfa.show')
            ->with('success', __('auth.mfa_enabled'));
    }

    public function disable(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
        ]);

        /** @var User $user */
        $user = Auth::user();

        $user->forgetMfaRememberedDevices();
        $user->forceFill([
            'google2fa_secret' => null,
            'google2fa_recovery_codes' => null,
        ])->save();

        app(Authenticator::class)->boot($request)->logout();
        AuditLog::record('mfa_disabled', 'auth', (string) $user->id);

        return redirect()->route('mfa.show')
            ->with('success', __('auth.mfa_disabled'));
    }

    public function regenerateRecoveryCodes(Request $request): RedirectResponse
    {
        $request->validate(['current_password' => ['required', 'current_password']]);

        /** @var User $user */
        $user = Auth::user();
        abort_unless($user->hasTwoFactorEnabled(), 404);

        $plainCodes = $this->generateRecoveryCodes();
        $user->forceFill([
            'google2fa_recovery_codes' => array_map(fn (string $code): string => Hash::make($code), $plainCodes),
        ])->save();

        $request->session()->put('mfa_plain_recovery_codes', $plainCodes);
        AuditLog::record('mfa_recovery_codes_regenerated', 'auth', (string) $user->id);

        return redirect()->route('mfa.show')
            ->with('success', __('auth.mfa_recovery_codes_regenerated'));
    }

    public function challenge(): View|RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $this->settings->shouldChallenge($user)) {
            return redirect()->to($this->destinationFor($user));
        }

        if (! $user->hasTwoFactorEnabled()) {
            return redirect()->route('mfa.show')
                ->with('warning', __('auth.mfa_setup_required'));
        }

        if (session(config('google2fa.session_var').'.auth_passed')) {
            return redirect()->intended($this->destinationFor($user));
        }

        return view('auth.mfa.challenge', [
            'rememberDays' => $this->settings->rememberDeviceDays(),
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'one_time_password' => ['nullable', 'digits:6', 'required_without:recovery_code'],
            'recovery_code' => ['nullable', 'string', 'required_without:one_time_password'],
            'remember_device' => ['nullable', 'boolean'],
        ]);

        /** @var User|null $user */
        $user = Auth::user();

        if (! $user || ! $user->hasTwoFactorEnabled()) {
            return redirect()->route('login');
        }

        $valid = false;

        if ($request->filled('one_time_password')) {
            $valid = $this->google2fa->verifyKey(
                (string) $user->google2fa_secret,
                (string) $request->input('one_time_password'),
            );
        }

        if (! $valid && $request->filled('recovery_code')) {
            $valid = $user->consumeRecoveryCode((string) $request->input('recovery_code'));
        }

        if (! $valid) {
            return back()
                ->withErrors(['one_time_password' => __('auth.mfa_invalid_code')])
                ->withInput();
        }

        app(Authenticator::class)->boot($request)->login();

        $response = redirect()->intended($this->destinationFor($user));

        if ($request->boolean('remember_device') && $this->settings->rememberDeviceDays() > 0) {
            $response = $user->rememberMfaDevice($response, $this->settings->rememberDeviceDays());
        }

        AuditLog::record('mfa_verified', 'auth', (string) $user->id);

        return $response;
    }

    /**
     * @return list<string>
     */
    private function generateRecoveryCodes(): array
    {
        return collect(range(1, 8))
            ->map(fn (): string => Str::upper(Str::random(5).'-'.Str::random(5)))
            ->all();
    }

    private function destinationFor(User $user): string
    {
        if ($user->canAccessAdminArea()) {
            return route('admin.dashboard');
        }

        if ($user->hasRole('applicant')) {
            return route('applicant.dashboard');
        }

        return route('home');
    }

    private function renderSvg(string $url): string
    {
        $renderer = new ImageRenderer(new RendererStyle(280), new SvgImageBackEnd);

        return (new Writer($renderer))->writeString($url);
    }
}
