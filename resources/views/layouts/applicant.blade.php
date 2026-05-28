<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="ltr" class="locale-{{ app()->getLocale() }} lang-{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', __('menus.dashboard')) &mdash; {{ \App\Models\Setting::get('org.name', config('app.name')) }}</title>
    @include('partials.favicon')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>if(localStorage.getItem('theme')==='dark')document.documentElement.classList.add('dark');</script>
    @php
    $themePrimary = \App\Models\Setting::get('appearance.primary_color', '#1A56DB');
    $themeSidebar = \App\Models\Setting::get('appearance.sidebar_color', '#1E3A8A');
    $themeAccent  = \App\Models\Setting::get('appearance.accent_color',  '#FF6B2B');
    @endphp
    <style>
    :root {
        --color-brand:        {{ $themePrimary }};
        --color-brand-dark:   color-mix(in srgb, {{ $themePrimary }} 80%, black);
        --color-navy:         {{ $themeSidebar }};
        --color-navy-dark:    color-mix(in srgb, {{ $themeSidebar }} 80%, black);
        --color-accent:       {{ $themeAccent }};
        --color-accent-dark:  color-mix(in srgb, {{ $themeAccent }} 80%, black);
        --color-brand-muted:  color-mix(in srgb, {{ $themePrimary }} 12%, white);
        --color-accent-muted: color-mix(in srgb, {{ $themeAccent }} 12%, white);
    }
    </style>
</head>
<body class="min-h-screen bg-gray-50 font-sans text-gray-900 antialiased locale-{{ app()->getLocale() }} lang-{{ app()->getLocale() }}"
      x-data="{ mobileOpen: false, userOpen: false, darkMode: localStorage.getItem('theme')==='dark', toggleDark(){ this.darkMode=!this.darkMode; localStorage.setItem('theme',this.darkMode?'dark':'light'); document.documentElement.classList.toggle('dark',this.darkMode); } }">

@php
    $orgLogo      = \App\Models\Setting::get('org.logo', '');
    $orgName      = \App\Models\Setting::get('org.name', config('app.name'));
    $applicant    = auth()->user()?->applicant;
    $unreadCount  = $applicant?->notifications()->whereNull('read_at')->count() ?? 0;
    $avatarLetter = strtoupper(substr($applicant?->first_name ?? auth()->user()?->name ?? 'A', 0, 1));
    $navItems = [
        ['route' => 'applicant.dashboard',          'label' => __('menus.dashboard'),        'match' => 'applicant.dashboard',
         'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>'],
        ['route' => 'applicant.applications.index', 'label' => __('menus.my_applications'),  'match' => 'applicant.applications.*',
         'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>'],
        ['route' => 'applicant.notifications.index','label' => __('menus.notifications'),     'match' => 'applicant.notifications.*',
         'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>'],
        ['route' => 'applicant.profile.show',       'label' => __('menus.profile'),           'match' => 'applicant.profile.*',
         'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>'],
        ['route' => 'applicant.vacancies.index',    'label' => __('vacancies.job_vacancies'), 'match' => 'applicant.vacancies.*',
         'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>'],
        ['route' => 'announcements.index',          'label' => __('menus.announcements'),     'match' => 'announcements.*',
         'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>'],
    ];
@endphp

{{-- ══════════════════════════════════════════════ TOP HEADER ══ --}}
<header class="fixed top-0 inset-x-0 z-50 h-14 sm:h-16 bg-white border-b border-gray-200 flex items-center px-3 sm:px-6 gap-2">

    {{-- Hamburger (mobile only) --}}
    <button @click="mobileOpen = true"
            class="lg:hidden flex items-center justify-center h-9 w-9 rounded-lg text-gray-500 hover:bg-gray-100 transition shrink-0">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
    </button>

    {{-- Logo + org name --}}
    <a href="{{ route('home') }}" class="flex items-center gap-2 shrink-0 min-w-0">
        @if($orgLogo)
            <img src="{{ Storage::url($orgLogo) }}" alt="{{ $orgName }}" class="h-7 sm:h-8 w-auto object-contain shrink-0">
        @endif
        <span class="text-sm sm:text-base font-bold text-blue-700 tracking-tight truncate max-w-28 sm:max-w-48 lg:max-w-xs">{{ $orgName }}</span>
    </a>

    <div class="flex-1"></div>

    {{-- Language toggle (desktop) --}}
    @if(\App\Models\Setting::get('localization.show_language_switcher', true))
    <div class="hidden sm:flex items-center rounded-lg border border-gray-200 overflow-hidden text-xs font-semibold">
        <a href="{{ route('lang.switch', 'en') }}"
           class="px-3 py-1.5 transition {{ app()->getLocale() === 'en' ? 'bg-blue-600 text-white' : 'text-gray-500 hover:bg-gray-50' }}">EN</a>
        <a href="{{ route('lang.switch', 'am') }}"
           class="px-3 py-1.5 transition {{ app()->getLocale() === 'am' ? 'bg-blue-600 text-white' : 'text-gray-500 hover:bg-gray-50' }}">አማ</a>
    </div>
    @endif

    {{-- Dark / Light toggle --}}
    <button @click="toggleDark()" title="Toggle dark mode"
            class="flex items-center justify-center h-9 w-9 rounded-lg text-gray-500 hover:bg-gray-100 transition shrink-0">
        <svg x-show="!darkMode" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
        </svg>
        <svg x-show="darkMode" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
        </svg>
    </button>

    {{-- Notification bell --}}
    <a href="{{ route('applicant.notifications.index') }}"
       class="relative flex items-center justify-center h-9 w-9 rounded-lg text-gray-500 hover:text-blue-600 hover:bg-blue-50 transition">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        @if($unreadCount > 0)
        <span class="absolute top-0.5 right-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[9px] font-bold text-white leading-none">
            {{ $unreadCount > 9 ? '9+' : $unreadCount }}
        </span>
        @endif
    </a>

    {{-- User avatar / dropdown (desktop) --}}
    <div class="relative hidden sm:block">
        <button @click="userOpen = !userOpen" @click.outside="userOpen = false"
                class="flex items-center gap-2 rounded-xl px-2 py-1.5 hover:bg-gray-100 transition">
            <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center shrink-0 overflow-hidden ring-2 ring-blue-50">
                @if($applicant?->profile_photo_path)
                    <img src="{{ route('applicant.profile.photo') }}" class="h-8 w-8 rounded-full object-cover" alt="">
                @else
                    <span class="text-sm font-bold text-blue-700">{{ $avatarLetter }}</span>
                @endif
            </div>
            <span class="text-sm font-medium text-gray-700 max-w-36 truncate hidden md:block">
                {{ $applicant?->full_name ?? auth()->user()?->name }}
            </span>
            <svg class="h-4 w-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <div x-show="userOpen"
             x-transition:enter="transition ease-out duration-100"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-75"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="absolute right-0 mt-2 w-52 rounded-2xl border border-gray-200 bg-white shadow-xl py-1.5 z-50"
             style="display:none">
            <div class="px-4 py-2.5 border-b border-gray-100">
                <p class="text-sm font-semibold text-gray-900 truncate">{{ $applicant?->full_name ?? auth()->user()?->name }}</p>
                <p class="text-xs text-gray-400 truncate mt-0.5">{{ auth()->user()?->email }}</p>
            </div>
            <div class="py-1">
                <a href="{{ route('applicant.profile.show') }}" @click="userOpen = false"
                   class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition">
                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    {{ __('menus.profile') }}
                </a>
            </div>
            <div class="border-t border-gray-100 pt-1">
                <form method="POST" action="{{ route('applicant.logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-2.5 px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        {{ __('menus.logout') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>

{{-- ════════════════════════════ MOBILE DRAWER (slide from left) ══ --}}
{{-- Backdrop --}}
<div x-show="mobileOpen"
     @click="mobileOpen = false"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 bg-black/50 z-40 lg:hidden"
     style="display:none"></div>

{{-- Drawer panel --}}
<div x-show="mobileOpen"
     x-transition:enter="transition ease-out duration-250"
     x-transition:enter-start="-translate-x-full"
     x-transition:enter-end="translate-x-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="translate-x-0"
     x-transition:leave-end="-translate-x-full"
     class="fixed top-0 left-0 bottom-0 w-72 bg-white z-50 flex flex-col lg:hidden shadow-2xl"
     style="display:none">

    {{-- Drawer header --}}
    <div class="flex items-center justify-between px-4 h-14 border-b border-gray-100 shrink-0">
        <a href="{{ route('home') }}" class="flex items-center gap-2" @click="mobileOpen = false">
            @if($orgLogo)
                <img src="{{ Storage::url($orgLogo) }}" alt="{{ $orgName }}" class="h-7 w-auto object-contain">
            @else
                <span class="text-sm font-bold text-blue-700">{{ $orgName }}</span>
            @endif
        </a>
        <button @click="mobileOpen = false"
                class="flex items-center justify-center h-8 w-8 rounded-lg text-gray-400 hover:bg-gray-100 transition">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    {{-- User card --}}
    <div class="px-4 py-4 border-b border-gray-100 shrink-0">
        <div class="flex items-center gap-3">
            <div class="h-11 w-11 rounded-full bg-blue-100 flex items-center justify-center shrink-0 overflow-hidden ring-2 ring-blue-50">
                @if($applicant?->profile_photo_path)
                    <img src="{{ route('applicant.profile.photo') }}" class="h-11 w-11 rounded-full object-cover" alt="">
                @else
                    <span class="text-base font-bold text-blue-700">{{ $avatarLetter }}</span>
                @endif
            </div>
            <div class="min-w-0">
                <p class="text-sm font-semibold text-gray-900 truncate">{{ $applicant?->full_name ?? auth()->user()?->name }}</p>
                @if($applicant?->applicant_code)
                <p class="text-xs text-gray-400 font-mono">{{ $applicant->applicant_code }}</p>
                @else
                <p class="text-xs text-gray-400 truncate">{{ auth()->user()?->email }}</p>
                @endif
            </div>
        </div>

        @if($applicant)
        @php $pct = $applicant->profileCompletionPercentage(); @endphp
        <div class="mt-3">
            <div class="flex justify-between text-xs mb-1">
                <span class="text-gray-500">{{ __('applicant.profile_completion') }}</span>
                <span class="font-bold {{ $pct === 100 ? 'text-green-600' : 'text-blue-600' }}">{{ $pct }}%</span>
            </div>
            <div class="h-1.5 rounded-full bg-gray-100">
                <div class="h-1.5 rounded-full {{ $pct === 100 ? 'bg-green-500' : 'bg-blue-500' }}"
                     style="width: {{ $pct }}%"></div>
            </div>
        </div>
        @endif
    </div>

    {{-- Nav links --}}
    <nav class="flex-1 overflow-y-auto p-3 space-y-0.5">
        @foreach($navItems as $item)
        @php $active = request()->routeIs($item['match']); @endphp
        <a href="{{ route($item['route']) }}" @click="mobileOpen = false"
           class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium transition
                  {{ $active ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50' }}">
            <svg class="h-5 w-5 shrink-0 {{ $active ? 'text-white' : 'text-gray-400' }}"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                {!! $item['icon'] !!}
            </svg>
            <span class="flex-1">{{ $item['label'] }}</span>
            @if($item['match'] === 'applicant.notifications.*' && $unreadCount > 0)
            <span class="inline-flex items-center justify-center h-5 min-w-5 rounded-full px-1 text-[10px] font-bold
                         {{ $active ? 'bg-white/25 text-white' : 'bg-red-500 text-white' }}">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
            @endif
        </a>
        @endforeach
    </nav>

    {{-- Drawer footer: language + logout --}}
    <div class="p-3 border-t border-gray-100 space-y-2 shrink-0">
        @if(\App\Models\Setting::get('localization.show_language_switcher', true))
        <div class="flex items-center rounded-xl border border-gray-200 overflow-hidden text-xs font-semibold">
            <a href="{{ route('lang.switch', 'en') }}" @click="mobileOpen = false"
               class="flex-1 text-center py-2.5 transition {{ app()->getLocale() === 'en' ? 'bg-blue-600 text-white' : 'text-gray-500 hover:bg-gray-50' }}">EN</a>
            <a href="{{ route('lang.switch', 'am') }}" @click="mobileOpen = false"
               class="flex-1 text-center py-2.5 transition {{ app()->getLocale() === 'am' ? 'bg-blue-600 text-white' : 'text-gray-500 hover:bg-gray-50' }}">አማ</a>
        </div>
        @endif
        <form method="POST" action="{{ route('applicant.logout') }}">
            @csrf
            <button type="submit"
                    class="w-full flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium text-red-600 hover:bg-red-50 transition">
                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                {{ __('menus.logout') }}
            </button>
        </form>
    </div>
</div>

{{-- ════════════════════════════════════════════ SIDEBAR (lg+) ══ --}}
<aside class="fixed top-16 left-0 bottom-0 w-64 bg-white border-r border-gray-200 hidden lg:flex flex-col z-40 overflow-y-auto">

    {{-- User card --}}
    <div class="p-4 border-b border-gray-100">
        <div class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center shrink-0 overflow-hidden ring-2 ring-blue-50">
                @if($applicant?->profile_photo_path)
                    <img src="{{ route('applicant.profile.photo') }}" class="h-10 w-10 rounded-full object-cover" alt="">
                @else
                    <span class="text-base font-bold text-blue-700">{{ $avatarLetter }}</span>
                @endif
            </div>
            <div class="min-w-0">
                <p class="text-sm font-semibold text-gray-900 truncate">{{ $applicant?->full_name ?? auth()->user()?->name }}</p>
                @if($applicant?->applicant_code)
                <p class="text-xs text-gray-400 font-mono">{{ $applicant->applicant_code }}</p>
                @endif
            </div>
        </div>

        @if($applicant)
        @php $pct = $applicant->profileCompletionPercentage(); @endphp
        <div class="mt-3">
            <div class="flex justify-between text-xs mb-1">
                <span class="text-gray-500">{{ __('applicant.profile_completion') }}</span>
                <span class="font-bold {{ $pct === 100 ? 'text-green-600' : 'text-blue-600' }}">{{ $pct }}%</span>
            </div>
            <div class="h-1.5 rounded-full bg-gray-100">
                <div class="h-1.5 rounded-full {{ $pct === 100 ? 'bg-green-500' : 'bg-blue-500' }}"
                     style="width: {{ $pct }}%"></div>
            </div>
        </div>
        @endif
    </div>

    {{-- Nav --}}
    <nav class="flex-1 p-3 space-y-0.5">
        @foreach($navItems as $item)
        @php $active = request()->routeIs($item['match']); @endphp
        <a href="{{ route($item['route']) }}"
           class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition group
                  {{ $active ? 'bg-blue-600 text-white shadow-sm shadow-blue-200' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
            <svg class="h-4 w-4 shrink-0 {{ $active ? 'text-white' : 'text-gray-400 group-hover:text-gray-600' }}"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                {!! $item['icon'] !!}
            </svg>
            <span class="flex-1 truncate">{{ $item['label'] }}</span>
            @if($item['match'] === 'applicant.notifications.*' && $unreadCount > 0)
            <span class="inline-flex items-center justify-center h-5 min-w-5 rounded-full px-1 text-[10px] font-bold
                         {{ $active ? 'bg-white/25 text-white' : 'bg-red-500 text-white' }}">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
            @endif
        </a>
        @endforeach
    </nav>

    {{-- Sidebar footer: language + logout --}}
    <div class="p-3 border-t border-gray-100 space-y-1">
        @if(\App\Models\Setting::get('localization.show_language_switcher', true))
        <div class="flex items-center rounded-xl border border-gray-200 overflow-hidden text-xs font-semibold">
            <a href="{{ route('lang.switch', 'en') }}"
               class="flex-1 text-center py-2 transition {{ app()->getLocale() === 'en' ? 'bg-blue-600 text-white' : 'text-gray-500 hover:bg-gray-50' }}">EN</a>
            <a href="{{ route('lang.switch', 'am') }}"
               class="flex-1 text-center py-2 transition {{ app()->getLocale() === 'am' ? 'bg-blue-600 text-white' : 'text-gray-500 hover:bg-gray-50' }}">አማ</a>
        </div>
        @endif
        <form method="POST" action="{{ route('applicant.logout') }}">
            @csrf
            <button type="submit"
                    class="w-full flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-sm font-medium text-gray-500 hover:bg-red-50 hover:text-red-600 transition">
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                {{ __('menus.logout') }}
            </button>
        </form>
    </div>
</aside>

{{-- ════════════════════════════════════════════════ MAIN AREA ══ --}}
<div class="lg:pl-64 pt-14 sm:pt-16 min-h-screen flex flex-col">

    {{-- Toast: success --}}
    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="flex items-center gap-3 mx-3 mt-3 sm:mx-6 sm:mt-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 shadow-sm">
        <svg class="h-4 w-4 shrink-0 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        <span class="flex-1 min-w-0">{{ session('success') }}</span>
        <button @click="show = false" class="shrink-0 text-green-400 hover:text-green-600 transition">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>
    @endif

    {{-- Toast: error --}}
    @if(session('error'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="flex items-center gap-3 mx-3 mt-3 sm:mx-6 sm:mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 shadow-sm">
        <svg class="h-4 w-4 shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span class="flex-1 min-w-0">{{ session('error') }}</span>
        <button @click="show = false" class="shrink-0 text-red-400 hover:text-red-600 transition">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>
    @endif

    {{-- Validation errors --}}
    @if($errors->any())
    <div class="mx-3 mt-3 sm:mx-6 sm:mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        <ul class="list-disc list-inside space-y-0.5">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Page content --}}
    <main class="flex-1 px-3 py-5 sm:px-6 sm:py-6 pb-20 lg:pb-6">
        @yield('content')
    </main>
</div>

{{-- ══════════════════════════════════ FIXED BOTTOM NAV (mobile) ══ --}}
<nav class="fixed bottom-0 inset-x-0 z-50 lg:hidden bg-white border-t border-gray-200"
     style="padding-bottom: env(safe-area-inset-bottom);">
    @php
        $bottomNav = [
            [
                'route' => 'applicant.dashboard',
                'match' => 'applicant.dashboard',
                'label' => __('menus.dashboard'),
                'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>',
            ],
            [
                'route' => 'applicant.applications.index',
                'match' => 'applicant.applications.*',
                'label' => __('menus.my_applications'),
                'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
            ],
            [
                'route' => 'applicant.vacancies.index',
                'match' => 'applicant.vacancies.*',
                'label' => __('vacancies.job_vacancies'),
                'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>',
            ],
            [
                'route' => 'applicant.notifications.index',
                'match' => 'applicant.notifications.*',
                'label' => __('menus.notifications'),
                'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>',
            ],
            [
                'route' => 'applicant.profile.show',
                'match' => 'applicant.profile.*',
                'label' => __('menus.profile'),
                'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>',
            ],
        ];
    @endphp
    <div class="flex items-stretch h-14">
        @foreach($bottomNav as $item)
        @php $active = request()->routeIs($item['match']); @endphp
        <a href="{{ route($item['route']) }}"
           class="relative flex-1 flex flex-col items-center justify-center gap-0.5 py-1.5 transition
                  {{ $active ? 'text-blue-600' : 'text-gray-400 hover:text-gray-600' }}">

            {{-- Active top indicator --}}
            @if($active)
            <span class="absolute top-0 left-1/2 -translate-x-1/2 w-8 h-0.5 rounded-full bg-blue-600"></span>
            @endif

            {{-- Icon --}}
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                {!! $item['icon'] !!}
            </svg>

            {{-- Label — clipped to 1 line, very small --}}
            <span class="text-[9px] leading-none font-medium w-full text-center truncate px-0.5">
                {{ $item['label'] }}
            </span>

            {{-- Notification dot --}}
            @if($item['match'] === 'applicant.notifications.*' && $unreadCount > 0)
            <span class="absolute top-1 left-1/2 translate-x-1.5 h-2 w-2 rounded-full bg-red-500 ring-1 ring-white"></span>
            @endif
        </a>
        @endforeach
    </div>
</nav>

@stack('scripts')
</body>
</html>

