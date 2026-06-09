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
<body class="admin-auth-shell h-full font-sans text-slate-200 antialiased">

<div class="flex min-h-full flex-col items-center justify-center px-4 py-12">
    <div class="w-full max-w-sm">

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
            <p class="mt-1 text-sm text-slate-400">Two-Factor Authentication</p>
        </div>

        <div class="rounded-2xl border border-slate-700/50 bg-slate-800 p-8 shadow-xl">
            <div class="mb-5 flex items-center gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[var(--color-brand-muted)] text-[var(--color-brand)]">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 8.25h3" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-white">Verification Required</h2>
                    <p class="text-xs text-slate-400">Open your authenticator app and enter the code</p>
                </div>
            </div>

            @if(isset($message) && $message)
                <div class="mb-4 rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-400">
                    {{ $message }}
                </div>
            @endif

            @if($errors->has('one_time_password'))
                <div class="mb-4 rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-400">
                    {{ $errors->first('one_time_password') }}
                </div>
            @endif

            <form method="POST" action="{{ request()->url() }}" class="space-y-4" autocomplete="off">
                @csrf

                <div>
                    <label for="one_time_password" class="block text-sm font-medium text-slate-300">
                        6-Digit Code
                    </label>
                    <input type="text"
                           id="one_time_password"
                           name="one_time_password"
                           inputmode="numeric"
                           pattern="[0-9]{6}"
                           maxlength="6"
                           autofocus
                           autocomplete="one-time-code"
                           placeholder="000000"
                           class="mt-1 block w-full rounded-lg border bg-slate-700 px-3 py-2.5 text-center text-xl font-mono tracking-[0.4em] text-white placeholder-slate-500
                                  admin-theme-focus focus:outline-none focus:ring-1
                                  {{ isset($message) && $message ? 'border-red-500' : 'border-slate-600' }}">
                </div>

                <button type="submit"
                        class="admin-theme-primary w-full rounded-lg px-4 py-2.5 text-sm font-semibold text-white transition focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)] focus:ring-offset-2 focus:ring-offset-slate-800">
                    Verify
                </button>
            </form>
        </div>

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
