<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use PragmaRX\Google2FA\Google2FA;
use PragmaRX\Google2FALaravel\Support\Authenticator;

class AdminTwoFactorController extends Controller
{
    public function __construct(private readonly Google2FA $google2fa) {}

    public function show(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->hasTwoFactorEnabled()) {
            return view('admin.two-factor.setup', ['enabled' => true]);
        }

        if (! $request->session()->has('2fa_setup_secret')) {
            $request->session()->put('2fa_setup_secret', $this->google2fa->generateSecretKey());
        }

        $secret = $request->session()->get('2fa_setup_secret');
        $qrCodeUrl = $this->google2fa->getQRCodeUrl(config('app.name'), $user->email, $secret);

        return view('admin.two-factor.setup', [
            'enabled' => false,
            'secret' => $secret,
            'qrCodeSvg' => $this->renderSvg($qrCodeUrl),
        ]);
    }

    public function enable(Request $request): RedirectResponse
    {
        $request->validate([
            'one_time_password' => ['required', 'digits:6'],
        ]);

        $secret = $request->session()->get('2fa_setup_secret');

        if (! $secret || ! $this->google2fa->verifyKey($secret, $request->input('one_time_password'))) {
            return back()
                ->withErrors(['one_time_password' => 'Incorrect code. Confirm your authenticator app is synced and try again.'])
                ->withInput();
        }

        /** @var User $user */
        $user = Auth::user();
        $user->google2fa_secret = $secret;
        $user->save();

        $request->session()->forget('2fa_setup_secret');
        app(Authenticator::class)->boot($request)->login();

        return redirect()->route('admin.two-factor.show')
            ->with('success', 'Two-factor authentication is now enabled.');
    }

    public function disable(Request $request): RedirectResponse
    {
        $request->validate([
            'one_time_password' => ['required', 'digits:6'],
        ]);

        /** @var User $user */
        $user = Auth::user();

        if (! $this->google2fa->verifyKey((string) $user->google2fa_secret, $request->input('one_time_password'))) {
            return back()
                ->withErrors(['one_time_password' => 'Incorrect code. Two-factor authentication was not disabled.'])
                ->withInput();
        }

        $user->google2fa_secret = null;
        $user->save();

        app(Authenticator::class)->boot($request)->logout();

        return redirect()->route('admin.two-factor.show')
            ->with('success', 'Two-factor authentication has been disabled.');
    }

    private function renderSvg(string $url): string
    {
        $renderer = new ImageRenderer(new RendererStyle(280), new SvgImageBackEnd);

        return (new Writer($renderer))->writeString($url);
    }
}
