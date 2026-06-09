<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('auth.reset_password') }} — {{ \App\Models\Setting::get('org.name', config('app.name')) }}</title>
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
                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-green-600/15">
                    <svg class="h-6 w-6 text-green-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>
                    </svg>
                </div>
                <h2 class="text-base font-semibold text-white">{{ __('auth.reset_password') }}</h2>
                <p class="mt-1 text-sm text-slate-400">{{ __('auth.reset_password_hint') }}</p>
            </div>

            @if($errors->any())
            <div class="mb-4 rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-400">
                {{ $errors->first() }}
            </div>
            @endif

            <form method="POST" action="{{ route('admin.password.reset') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div>
                    <label for="password" class="block text-sm font-medium text-slate-300">{{ __('auth.new_password') }}</label>
                    <input type="password" id="password" name="password"
                           required autocomplete="new-password"
                           class="mt-1 block w-full rounded-lg border border-slate-600 bg-slate-700 px-3 py-2.5 text-sm text-white placeholder-slate-400
                                  admin-theme-focus focus:outline-none focus:ring-1
                                  @error('password') border-red-500 @enderror">
                    @error('password')
                    <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-slate-300">{{ __('auth.confirm_password') }}</label>
                    <input type="password" id="password_confirmation" name="password_confirmation"
                           required autocomplete="new-password"
                           class="mt-1 block w-full rounded-lg border border-slate-600 bg-slate-700 px-3 py-2.5 text-sm text-white placeholder-slate-400
                                  admin-theme-focus focus:outline-none focus:ring-1">
                </div>

                <button type="submit"
                        class="admin-theme-primary w-full rounded-lg px-4 py-2.5 text-sm font-semibold text-white transition focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)] focus:ring-offset-2 focus:ring-offset-slate-800">
                    {{ __('auth.reset_password_button') }}
                </button>
            </form>
        </div>

        <p class="mt-6 text-center text-xs text-slate-500">
            <a href="{{ route('login') }}" class="hover:text-slate-300 transition">← {{ __('auth.back_to_login') }}</a>
        </p>
    </div>
</div>

</body>
</html>
