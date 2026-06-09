<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('auth.verify_otp') }} — {{ \App\Models\Setting::get('org.name', config('app.name')) }}</title>
    @include('partials.favicon')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.admin-theme')
</head>
<body class="admin-auth-shell h-full font-sans antialiased">

<div class="flex min-h-full flex-col items-center justify-center px-4 py-12">
    <div class="w-full max-w-sm">

        {{-- Brand --}}
        <div class="mb-8 text-center">
            @php $orgLogo = \App\Models\Setting::get('org.logo', ''); @endphp
            @if($orgLogo)
            <img src="{{ Storage::url($orgLogo) }}" alt="" class="mx-auto mb-4 h-12 w-auto object-contain">
            @else
            <div class="admin-theme-primary mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-xl text-lg font-bold text-white">
                {{ mb_substr(\App\Models\Setting::get('org.name', config('app.name')), 0, 2) }}
            </div>
            @endif
            <h1 class="text-xl font-bold text-white">{{ \App\Models\Setting::get('org.name', config('app.name')) }}</h1>
            <p class="mt-1 text-sm text-slate-400">{{ __('menus.admin_panel') }}</p>
        </div>

        <div class="rounded-2xl border border-slate-700/50 bg-slate-800 p-8 shadow-xl">

            {{-- Icon + heading --}}
            <div class="mb-6 text-center">
                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-[var(--color-brand-muted)]">
                    <svg class="h-6 w-6 text-[var(--color-brand)]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M21.75 9v.906a2.25 2.25 0 01-1.183 1.981l-6.478 3.488M2.25 9v.906a2.25 2.25 0 001.183 1.981l6.478 3.488m8.839 2.51l-4.66-2.51m0 0l-1.023-.55a2.25 2.25 0 00-2.134 0l-1.022.55m0 0l-4.661 2.51m16.5 1.615a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V8.844a2.25 2.25 0 011.183-1.981l7.5-4.039a2.25 2.25 0 012.134 0l7.5 4.039a2.25 2.25 0 011.183 1.98V19.5z"/>
                    </svg>
                </div>
                <h2 class="text-base font-semibold text-white">{{ __('auth.verify_otp') }}</h2>
                <p class="mt-1 text-sm text-slate-400">
                    {{ __('auth.otp_sent_to', ['email' => session('admin_password_reset_email')]) }}
                </p>
            </div>

            @if(session('info'))
            <div class="mb-4 rounded-lg border border-[var(--color-brand)]/30 bg-[var(--color-brand-muted)] px-4 py-3 text-sm text-[var(--color-brand)]">
                {{ session('info') }}
            </div>
            @endif

            @if($errors->any())
            <div class="mb-4 rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-400">
                {{ $errors->first() }}
            </div>
            @endif

            <form method="POST" action="{{ route('admin.password.verify-otp') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="otp" class="block text-sm font-medium text-slate-300">{{ __('auth.otp_code') }}</label>
                    <input type="text" id="otp" name="otp"
                           inputmode="numeric" pattern="\d{6}" maxlength="6"
                           value="{{ old('otp') }}" required autofocus autocomplete="one-time-code"
                           placeholder="_ _ _ _ _ _"
                           class="mt-1 block w-full rounded-lg border border-slate-600 bg-slate-700 px-3 py-3 text-center text-xl font-bold tracking-[0.5em] text-white placeholder-slate-600
                                  admin-theme-focus focus:outline-none focus:ring-1
                                  @error('otp') border-red-500 @enderror">
                    <p class="mt-1.5 text-xs text-slate-500">{{ __('auth.otp_expires_hint') }}</p>
                </div>

                <button type="submit"
                        class="admin-theme-primary w-full rounded-lg px-4 py-2.5 text-sm font-semibold text-white transition focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)] focus:ring-offset-2 focus:ring-offset-slate-800">
                    {{ __('auth.verify_otp_button') }}
                </button>
            </form>

            <div class="mt-4 text-center">
                <form method="POST" action="{{ route('admin.password.email') }}">
                    @csrf
                    <input type="hidden" name="email" value="{{ session('admin_password_reset_email') }}">
                    <button type="submit" class="admin-theme-link text-sm transition">
                        {{ __('auth.resend_otp') }}
                    </button>
                </form>
            </div>
        </div>

        <p class="mt-6 text-center text-xs text-slate-500">
            <a href="{{ route('login') }}" class="hover:text-slate-300 transition">← {{ __('auth.back_to_login') }}</a>
        </p>
    </div>
</div>

</body>
</html>
