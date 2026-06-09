<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('menus.admin_login') }} — {{ \App\Models\Setting::get('org.name', config('app.name')) }}</title>
    @include('partials.favicon')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.admin-theme')
</head>
<body class="admin-auth-shell h-full font-sans antialiased">

<div class="flex min-h-full flex-col items-center justify-center px-4 py-12">

    {{-- Card --}}
    <div class="w-full max-w-sm">

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
            <p class="mt-1 text-sm text-slate-400">{{ __('menus.admin_panel') }}</p>
        </div>

        <div class="rounded-2xl border border-slate-700/50 bg-slate-800 p-8 shadow-xl">
            <h2 class="mb-6 text-base font-semibold text-white">{{ __('menus.sign_in') }}</h2>

            @if(session('warning'))
            <div class="mb-4 rounded-lg border border-amber-500/40 bg-amber-500/10 px-4 py-3 text-sm text-amber-300">
                {{ session('warning') }}
            </div>
            @endif

            @if($errors->any())
            <div class="mb-4 rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-400">
                {{ $errors->first() }}
            </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-300">{{ __('fields.email') }}</label>
                    <input type="email" id="email" name="email"
                           value="{{ old('email') }}" required autofocus autocomplete="email"
                           class="mt-1 block w-full rounded-lg border bg-slate-700 px-3 py-2.5 text-sm text-white placeholder-slate-400
                                  admin-theme-focus focus:outline-none focus:ring-1
                                  {{ $errors->has('email') ? 'border-red-500' : 'border-slate-600' }}">
                </div>

                <div>
                    <div class="flex items-center justify-between">
                        <label for="password" class="block text-sm font-medium text-slate-300">{{ __('fields.password') }}</label>
                        <a href="{{ route('admin.password.request') }}" class="admin-theme-link text-xs transition">
                            {{ __('auth.forgot_password') }}?
                        </a>
                    </div>
                    <input type="password" id="password" name="password"
                           required autocomplete="current-password"
                           class="mt-1 block w-full rounded-lg border bg-slate-700 px-3 py-2.5 text-sm text-white placeholder-slate-400
                                  admin-theme-focus focus:outline-none focus:ring-1
                                  {{ $errors->has('password') ? 'border-red-500' : 'border-slate-600' }}">
                </div>

                <div class="flex items-center">
                    <input type="checkbox" id="remember" name="remember"
                           class="h-4 w-4 rounded border-slate-600 bg-slate-700 text-[var(--color-brand)] focus:ring-[var(--color-brand)]">
                    <label for="remember" class="ml-2 text-sm text-slate-300">{{ __('applicant.remember_me') }}</label>
                </div>

                <button type="submit"
                        class="admin-theme-primary w-full rounded-lg px-4 py-2.5 text-sm font-semibold text-white transition focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)] focus:ring-offset-2 focus:ring-offset-slate-800">
                    {{ __('menus.sign_in') }}
                </button>
            </form>
        </div>

        <p class="mt-6 text-center text-xs text-slate-500">
            <a href="{{ route('home') }}" class="hover:text-slate-300 transition">← {{ __('applicant.back_to_jobs') }}</a>
        </p>
    </div>
</div>

</body>
</html>
