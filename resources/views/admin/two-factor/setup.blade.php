<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Authentication — {{ \App\Models\Setting::get('org.name', config('app.name')) }}</title>
    @include('partials.favicon')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.admin-theme')
</head>
<body class="admin-auth-shell min-h-full font-sans text-slate-200 antialiased">

<div class="flex min-h-screen flex-col items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">

        {{-- Logo / Brand --}}
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
            <p class="mt-1 text-sm text-slate-400">Two-Factor Authentication Setup</p>
        </div>

        {{-- Flash messages --}}
        @if(session('warning'))
            <div class="mb-4 rounded-lg border border-amber-500/40 bg-amber-500/10 px-4 py-3 text-sm font-medium text-white">
                {{ session('warning') }}
            </div>
        @endif

        @if(session('success'))
            <div class="mb-4 rounded-lg border border-green-500/40 bg-green-500/10 px-4 py-3 text-sm font-medium text-white">
                {{ session('success') }}
            </div>
        @endif

        @if($enabled)
            {{-- 2FA is active --}}
            <div class="rounded-2xl border border-slate-700/50 bg-slate-800 p-8 shadow-xl space-y-6">

                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-green-600/20 text-green-400">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-white">Two-factor authentication is enabled</p>
                        <p class="mt-1 text-xs text-slate-400">Your account is protected with a time-based one-time password.</p>
                    </div>
                </div>

                <hr class="border-slate-700">

                <div>
                    <p class="text-sm font-semibold text-white mb-1">Disable Two-Factor Authentication</p>
                    <p class="text-xs text-slate-400 mb-4">Enter your current authenticator code to confirm.</p>

                    <form method="POST" action="{{ route('admin.two-factor.disable') }}" class="space-y-4">
                        @csrf

                        <div>
                            <label for="one_time_password" class="block text-sm font-medium text-slate-300">Authenticator Code</label>
                            <input type="text"
                                   id="one_time_password"
                                   name="one_time_password"
                                   inputmode="numeric"
                                   pattern="[0-9]{6}"
                                   maxlength="6"
                                   autofocus
                                   autocomplete="one-time-code"
                                   placeholder="000000"
                                   class="mt-1 block w-full rounded-lg border bg-slate-700 px-3 py-2.5 text-center font-mono tracking-widest text-white placeholder-slate-500
                                          admin-theme-focus focus:outline-none focus:ring-1
                                          {{ $errors->has('one_time_password') ? 'border-red-500' : 'border-slate-600' }}">
                            @error('one_time_password')
                                <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit"
                                class="w-full rounded-lg border border-red-500/50 bg-red-500/10 px-4 py-2.5 text-sm font-semibold text-red-400 hover:bg-red-500/20 transition focus:outline-none focus:ring-2 focus:ring-red-500">
                            Disable Two-Factor Authentication
                        </button>
                    </form>
                </div>
            </div>

        @else
            {{-- Setup flow --}}
            <div class="rounded-2xl border border-slate-700/50 bg-slate-800 p-8 shadow-xl space-y-6">

                <div class="rounded-lg border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-300">
                    <strong>Required:</strong> Set up two-factor authentication to access the admin panel.
                    Use an authenticator app such as Google Authenticator, Authy, or 1Password.
                </div>

                <div>
                    <p class="text-sm font-semibold text-white">Step 1 — Scan the QR code</p>
                    <p class="mt-1 text-xs text-slate-400">Open your authenticator app, tap <strong class="text-slate-300">+</strong> or <em>Add account</em>, and scan the code below.</p>
                    <div class="mt-4 flex justify-center">
                        <div class="rounded-xl border border-slate-600 bg-white p-3 shadow-sm">
                            {!! $qrCodeSvg !!}
                        </div>
                    </div>
                </div>

                <div>
                    <p class="text-sm font-semibold text-white mb-1">Can't scan? Enter this key manually</p>
                    <p class="font-mono text-sm tracking-widest text-slate-300 bg-slate-700/50 rounded-lg px-4 py-2 border border-slate-600 select-all text-center">
                        {{ implode(' ', str_split($secret, 4)) }}
                    </p>
                </div>

                <hr class="border-slate-700">

                <form method="POST" action="{{ route('admin.two-factor.enable') }}" class="space-y-4">
                    @csrf

                    <div>
                        <p class="text-sm font-semibold text-white">Step 2 — Confirm with a code</p>
                        <p class="mt-1 text-xs text-slate-400">Enter the 6-digit code from your app to verify the setup.</p>
                        <input type="text"
                               id="one_time_password"
                               name="one_time_password"
                               inputmode="numeric"
                               pattern="[0-9]{6}"
                               maxlength="6"
                               autocomplete="one-time-code"
                               placeholder="000000"
                               class="mt-2 block w-full rounded-lg border bg-slate-700 px-3 py-2.5 text-center text-xl font-mono tracking-[0.4em] text-white placeholder-slate-500
                                      admin-theme-focus focus:outline-none focus:ring-1
                                      {{ $errors->has('one_time_password') ? 'border-red-500' : 'border-slate-600' }}">
                        @error('one_time_password')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit"
                            class="admin-theme-primary w-full rounded-lg px-4 py-2.5 text-sm font-semibold text-white transition focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)] focus:ring-offset-2 focus:ring-offset-slate-800">
                        Enable Two-Factor Authentication
                    </button>
                </form>

            </div>
        @endif

        <div class="mt-6 text-center text-xs">
            <form method="POST" action="{{ route('admin.logout') }}" class="inline">
                @csrf
                <button type="submit" class="text-slate-400 transition hover:text-slate-200">Sign out and use a different account</button>
            </form>
        </div>

    </div>
</div>

</body>
</html>
