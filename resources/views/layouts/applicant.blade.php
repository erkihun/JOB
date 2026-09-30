<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="ltr" class="locale-{{ app()->getLocale() }} lang-{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', __('applicant.nav_dashboard')) &mdash; {{ \App\Models\Setting::get('org.name', config('app.name')) }}</title>
    @include('partials.favicon')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>if(localStorage.getItem('theme')==='dark')document.documentElement.classList.add('dark');</script>
    {{-- Same validated theme (colours + palette mapping) as the admin and public site --}}
    @include('partials.admin-theme')
</head>
<body class="public-site min-h-screen bg-[#F5F7F7] text-gray-900 antialiased locale-{{ app()->getLocale() }} lang-{{ app()->getLocale() }}"
      x-data="{ userOpen: false, darkMode: localStorage.getItem('theme')==='dark', toggleDark(){ this.darkMode=!this.darkMode; localStorage.setItem('theme',this.darkMode?'dark':'light'); document.documentElement.classList.toggle('dark',this.darkMode); } }">

@php
    $orgLogo     = \App\Models\Setting::get('org.logo', '');
    $orgName     = \App\Models\Setting::get('org.name', config('app.name'));
    $applicant   = auth()->user()?->applicant;
    $unreadCount = $applicant?->notifications()->whereNull('read_at')->count() ?? 0;
    $displayName = $applicant?->full_name ?? auth()->user()?->name;
    $initials    = collect(preg_split('/\s+/', trim((string) $displayName)))->filter()->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('') ?: 'A';
    $showLang    = \App\Models\Setting::get('localization.show_language_switcher', true);
    $languages   = array_intersect_key(['en' => 'EN', 'am' => 'አማ'], array_flip((array) \App\Models\Setting::get('app.available_locales', ['en', 'am'])));
    $icons = [
        'home'  => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
        'jobs'  => 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
        'apps'  => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        'bell'  => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
        'user'  => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
    ];
    $navItems = [
        ['route' => 'applicant.dashboard',           'match' => 'applicant.dashboard',        'label' => __('applicant.nav_dashboard'),        'short' => __('applicant.nav_home'),     'icon' => 'home'],
        ['route' => 'applicant.vacancies.index',     'match' => 'applicant.vacancies.*',      'label' => __('public.find_jobs'),       'short' => __('applicant.nav_jobs'),     'icon' => 'jobs'],
        ['route' => 'applicant.applications.index',  'match' => 'applicant.applications.*',   'label' => __('menus.my_applications'),  'short' => __('applicant.nav_applications'), 'icon' => 'apps'],
        ['route' => 'applicant.notifications.index', 'match' => 'applicant.notifications.*',  'label' => __('menus.notifications'),    'short' => __('applicant.nav_messages'), 'icon' => 'bell', 'badge' => $unreadCount],
        ['route' => 'applicant.profile.show',        'match' => 'applicant.profile.*',        'label' => __('menus.profile'),          'short' => __('menus.profile'),          'icon' => 'user'],
    ];
@endphp

<a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-70 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-brand focus:shadow-lg">
    {{ __('public.skip_to_content') }}
</a>

{{-- ═════════════════════════════════════════════ HEADER ══ --}}
<header class="sticky top-0 z-50 border-b border-gray-200 bg-white">
    <div class="mx-auto flex h-16 max-w-6xl items-center gap-6 px-4 sm:px-6 lg:h-17 lg:px-8">
        <a href="{{ route('home') }}" class="flex min-w-0 shrink items-center gap-2.5">
            @if($orgLogo)
                <img src="{{ Storage::url($orgLogo) }}" alt="" class="h-9 w-auto shrink-0 object-contain">
            @else
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand text-white">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['jobs'] }}"/></svg>
                </span>
            @endif
            <span class="max-w-40 truncate text-[15px] font-extrabold text-gray-900 sm:max-w-56">{{ $orgName }}</span>
        </a>

        <nav class="hidden h-full flex-1 items-stretch gap-1 lg:flex" aria-label="{{ __('applicant.portal_navigation') }}">
            @foreach($navItems as $item)
            @php $active = request()->routeIs($item['match']); @endphp
            <a href="{{ route($item['route']) }}" @if($active) aria-current="page" @endif
               class="inline-flex items-center gap-2 whitespace-nowrap border-b-[3px] px-3 pt-0.75 text-[15px] font-semibold transition
                      {{ $active ? 'border-brand text-brand' : 'border-transparent text-gray-600 hover:border-gray-300 hover:text-gray-900' }}">
                {{ $item['label'] }}
                @if(!empty($item['badge']))
                <span class="rounded-full bg-accent-dark px-1.5 text-xs font-bold text-white">{{ $item['badge'] > 9 ? '9+' : $item['badge'] }}</span>
                @endif
            </a>
            @endforeach
        </nav>

        <div class="flex-1 lg:hidden"></div>

        @if($showLang && count($languages) > 1)
        <div class="flex items-center rounded-lg border border-gray-200 p-0.5 text-xs font-semibold" role="group" aria-label="{{ __('public.language') }}">
            @foreach($languages as $code => $label)
            <a href="{{ route('lang.switch', $code) }}" lang="{{ $code }}" @if(app()->getLocale() === $code) aria-current="true" @endif
               class="rounded-md px-2 py-1.5 transition {{ app()->getLocale() === $code ? 'bg-ink text-white' : 'text-gray-600 hover:text-gray-900' }}">{{ $label }}</a>
            @endforeach
        </div>
        @endif

        {{-- User menu --}}
        <div class="relative" @click.outside="userOpen = false" @keydown.escape.window="userOpen = false">
            <button type="button" @click="userOpen = !userOpen" :aria-expanded="userOpen.toString()" aria-haspopup="menu"
                    class="flex items-center gap-2 rounded-full p-0.5 transition hover:ring-4 hover:ring-gray-100">
                <span class="flex h-9 w-9 items-center justify-center overflow-hidden rounded-full bg-brand-muted text-sm font-bold text-brand-dark">
                    @if($applicant?->profile_photo_path)
                        <img src="{{ route('applicant.profile.photo') }}" class="h-9 w-9 object-cover" alt="">
                    @else
                        {{ $initials }}
                    @endif
                </span>
                <span class="sr-only">{{ __('applicant.account_menu') }}</span>
            </button>
            <div x-show="userOpen" x-cloak x-transition.opacity.duration.100ms role="menu"
                 class="absolute right-0 mt-2 w-60 overflow-hidden rounded-xl border border-gray-200 bg-white py-1.5 shadow-xl">
                <div class="border-b border-gray-100 px-4 py-2.5">
                    <p class="truncate text-sm font-bold text-gray-900">{{ $displayName }}</p>
                    <p class="mt-0.5 truncate text-xs text-gray-600">{{ auth()->user()?->email }}</p>
                    @if($applicant?->applicant_code)<p class="mt-0.5 font-mono text-xs text-gray-500">{{ $applicant->applicant_code }}</p>@endif
                </div>
                <a href="{{ route('applicant.profile.show') }}" class="block px-4 py-2 text-sm text-gray-800 hover:bg-gray-50" role="menuitem">{{ __('menus.profile') }}</a>
                <a href="{{ route('mfa.show') }}" class="block px-4 py-2 text-sm text-gray-800 hover:bg-gray-50" role="menuitem">{{ __('applicant.security') }}</a>
                <button type="button" @click="toggleDark()" class="flex w-full items-center justify-between px-4 py-2 text-left text-sm text-gray-800 hover:bg-gray-50" role="menuitem">
                    {{ __('applicant.dark_mode') }}
                    <span class="text-xs font-semibold text-gray-500" x-text="darkMode ? @js(__('applicant.on')) : @js(__('applicant.off'))"></span>
                </button>
                <form method="POST" action="{{ route('applicant.logout') }}" class="border-t border-gray-100 pt-1">
                    @csrf
                    <button type="submit" class="block w-full px-4 py-2 text-left text-sm font-semibold text-red-700 hover:bg-red-50" role="menuitem">{{ __('menus.logout') }}</button>
                </form>
            </div>
        </div>
    </div>
</header>

{{-- ═════════════════════════════════════════════ FLASH ══ --}}
@foreach(['success' => 'border-green-200 bg-green-50 text-green-900', 'error' => 'border-red-200 bg-red-50 text-red-900', 'warning' => 'border-amber-200 bg-amber-50 text-amber-900'] as $flashKey => $flashClass)
@if(session($flashKey))
<div class="mx-auto mt-4 max-w-6xl px-4 sm:px-6 lg:px-8" x-data="{ show: true }" x-show="show" @if($flashKey === 'success') x-init="setTimeout(() => show = false, 5000)" @endif
     role="{{ $flashKey === 'success' ? 'status' : 'alert' }}">
    <div class="flex items-start gap-3 rounded-xl border px-4 py-3 text-sm font-medium {{ $flashClass }}">
        <span class="flex-1">{{ session($flashKey) }}</span>
        <button type="button" @click="show = false" class="opacity-60 hover:opacity-100" aria-label="{{ __('public.close') }}">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
</div>
@endif
@endforeach

@if($errors->any())
<div class="mx-auto mt-4 max-w-6xl px-4 sm:px-6 lg:px-8" role="alert">
    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
        <p class="font-bold">{{ __('applicant.fix_errors_heading') }}</p>
        <ul class="mt-1 list-inside list-disc space-y-0.5">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
</div>
@endif

{{-- ═════════════════════════════════════════════ CONTENT ══ --}}
<main id="main-content" tabindex="-1" class="mx-auto max-w-6xl px-4 pb-28 pt-6 sm:px-6 sm:pt-8 lg:px-8 lg:pb-16">
    @yield('content')
</main>

{{-- ═══════════════════════════════════ BOTTOM NAV (mobile) ══ --}}
<nav class="fixed inset-x-0 bottom-0 z-50 border-t border-gray-200 bg-white lg:hidden" style="padding-bottom: env(safe-area-inset-bottom);" aria-label="{{ __('applicant.portal_navigation') }}">
    <div class="grid h-16 grid-cols-5">
        @foreach($navItems as $item)
        @php $active = request()->routeIs($item['match']); @endphp
        <a href="{{ route($item['route']) }}" @if($active) aria-current="page" @endif
           class="relative flex flex-col items-center justify-center gap-1 text-[11px] font-semibold transition {{ $active ? 'text-brand' : 'text-gray-500 hover:text-gray-800' }}">
            @if($active)<span class="absolute top-0 h-0.75 w-8 rounded-full bg-brand" aria-hidden="true"></span>@endif
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$item['icon']] }}"/></svg>
            <span class="w-full truncate px-0.5 text-center">{{ $item['short'] }}</span>
            @if(!empty($item['badge']))
            <span class="absolute left-1/2 top-2 ml-1.5 h-2.5 w-2.5 rounded-full bg-accent-dark ring-2 ring-white" aria-hidden="true"></span>
            @endif
        </a>
        @endforeach
    </div>
</nav>

@stack('scripts')
</body>
</html>
