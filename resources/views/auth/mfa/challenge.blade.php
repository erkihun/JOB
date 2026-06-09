<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('auth.mfa_challenge') }} - {{ \App\Models\Setting::get('org.name', config('app.name')) }}</title>
    @include('partials.favicon')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.admin-theme')
</head>
<body class="admin-auth-shell h-full font-sans text-slate-200 antialiased">
<div class="flex min-h-full flex-col items-center justify-center px-4 py-12">
    <div class="w-full max-w-sm">

        {{-- Header --}}
        <div class="mb-8 text-center">
            <div class="admin-theme-primary mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-xl text-lg font-bold text-white">
                MFA
            </div>
            <h1 class="text-xl font-bold text-white">{{ __('auth.mfa_challenge') }}</h1>
            <p class="mt-1 text-sm text-slate-400">{{ auth()->user()->email }}</p>
        </div>

        <div class="rounded-2xl border border-slate-700/50 bg-slate-800 p-8 shadow-xl">

            @if($errors->any())
                <div class="mb-4 rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-400">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('mfa.challenge') }}" class="space-y-4" id="mfa-form">
                @csrf

                {{-- Authenticator code panel (default) --}}
                <div id="panel-authenticator">
                    <label for="one_time_password" class="block text-sm font-medium text-slate-300">
                        {{ __('auth.mfa_code') }}
                    </label>
                    <input type="text"
                           id="one_time_password"
                           name="one_time_password"
                           inputmode="numeric"
                           pattern="[0-9]{6}"
                           maxlength="6"
                           autofocus
                           autocomplete="one-time-code"
                           value="{{ old('one_time_password') }}"
                           class="admin-theme-focus mt-1 block w-full rounded-lg border border-slate-600 bg-slate-700 px-3 py-2.5 text-center font-mono text-xl tracking-[0.4em] text-white placeholder-slate-500 focus:outline-none focus:ring-1">
                </div>

                {{-- Recovery code panel (hidden by default) --}}
                <div id="panel-recovery" class="hidden">
                    <label for="recovery_code" class="block text-sm font-medium text-slate-300">
                        {{ __('auth.recovery_code') }}
                    </label>
                    <input type="text"
                           id="recovery_code"
                           name="recovery_code"
                           autocomplete="off"
                           spellcheck="false"
                           value="{{ old('recovery_code') }}"
                           class="admin-theme-focus mt-1 block w-full rounded-lg border border-slate-600 bg-slate-700 px-3 py-2.5 font-mono text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-1"
                           placeholder="XXXXX-XXXXX">
                </div>

                @if($rememberDays > 0)
                    <label class="flex items-center gap-2 text-sm text-slate-300">
                        <input type="hidden" name="remember_device" value="0">
                        <input type="checkbox" name="remember_device" value="1" class="h-4 w-4 rounded border-slate-600 bg-slate-700 text-[var(--color-brand)] focus:ring-[var(--color-brand)]">
                        {{ __('auth.remember_this_device', ['days' => $rememberDays]) }}
                    </label>
                @endif

                <button type="submit"
                        class="admin-theme-primary w-full rounded-lg px-4 py-2.5 text-sm font-semibold text-white transition focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)] focus:ring-offset-2 focus:ring-offset-slate-800">
                    {{ __('auth.verify_mfa') }}
                </button>

                {{-- Toggle link --}}
                <div class="text-center">
                    <button type="button"
                            id="toggle-recovery"
                            class="text-xs text-slate-400 underline-offset-2 hover:text-slate-200 hover:underline focus:outline-none">
                        {{ __('auth.use_recovery_code') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    const toggleBtn    = document.getElementById('toggle-recovery');
    const panelAuth    = document.getElementById('panel-authenticator');
    const panelRec     = document.getElementById('panel-recovery');
    const inputAuth    = document.getElementById('one_time_password');
    const inputRec     = document.getElementById('recovery_code');

    // If the page reloaded with a recovery_code error, start in recovery mode
    let usingRecovery = {{ old('recovery_code') ? 'true' : 'false' }};

    function apply() {
        if (usingRecovery) {
            panelAuth.classList.add('hidden');
            panelRec.classList.remove('hidden');
            inputAuth.removeAttribute('name');
            inputRec.setAttribute('name', 'recovery_code');
            toggleBtn.textContent = {{ Js::from(__('auth.back_to_authenticator_code')) }};
            inputRec.focus();
        } else {
            panelRec.classList.add('hidden');
            panelAuth.classList.remove('hidden');
            inputRec.removeAttribute('name');
            inputAuth.setAttribute('name', 'one_time_password');
            toggleBtn.textContent = {{ Js::from(__('auth.use_recovery_code')) }};
            inputAuth.focus();
        }
    }

    apply();

    toggleBtn.addEventListener('click', function () {
        usingRecovery = !usingRecovery;
        apply();
    });
})();
</script>
</body>
</html>
