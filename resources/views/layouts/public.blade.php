<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name')) &mdash; {{ \App\Models\Setting::get('org.name', config('app.name')) }}</title>
    <meta name="description" content="@yield('meta_description', 'Job Vacancy Announcement System')">
    @include('partials.favicon')
    @if(app()->getLocale() === 'am')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Ethiopic:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>* { font-family: 'Noto Sans Ethiopic', sans-serif !important; }</style>
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 font-sans text-gray-900 antialiased"
      x-data="{ drawerOpen: false }">

@php
    $orgLogo = \App\Models\Setting::get('org.logo', '');
    $orgName = \App\Models\Setting::get('org.name', config('app.name'));
    $publicNav = [
        ['route' => 'home',               'label' => __('menus.home'),             'match' => 'home',            'icon' => 'home'],
        ['route' => 'announcements.index', 'label' => __('menus.announcements'),    'match' => 'announcements.*', 'icon' => 'announcements'],
        ['route' => 'vacancies.index',     'label' => __('vacancies.job_vacancies'),'match' => 'vacancies.*',     'icon' => 'vacancies'],
        ['route' => 'track.show',          'label' => __('menus.track_application'),'match' => 'track.*',         'icon' => 'track'],
    ];
@endphp

{{-- ══════════════════════════════════════════════════ HEADER ══ --}}
<header class="fixed top-0 inset-x-0 z-50 h-16 bg-white border-b border-gray-200 flex items-center px-4 sm:px-6 gap-3">

    {{-- Mobile hamburger --}}
    <button @click="drawerOpen = true"
            class="md:hidden flex items-center justify-center h-9 w-9 rounded-lg text-gray-500 hover:bg-gray-100 transition shrink-0">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
    </button>

    {{-- Logo + org name --}}
    <a href="{{ route('home') }}" class="flex items-center gap-2.5 shrink-0 min-w-0 mr-4">
        @if($orgLogo)
            <img src="{{ Storage::url($orgLogo) }}" alt="{{ $orgName }}" class="h-8 w-auto object-contain shrink-0">
        @endif
        <span class="text-sm sm:text-base font-bold text-blue-700 tracking-tight truncate max-w-32 sm:max-w-56 lg:max-w-xs">
            {{ $orgName }}
        </span>
    </a>

    {{-- Desktop nav --}}
    <nav class="hidden md:flex items-center gap-1 flex-1">
        @foreach($publicNav as $item)
        @php $active = request()->routeIs($item['match']); @endphp
        <a href="{{ route($item['route']) }}"
           class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition whitespace-nowrap
                  {{ $active ? 'bg-orange-500 text-white' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
            @switch($item['icon'])
                @case('home')
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10.5L12 3l9 7.5M5 9.5V21h5v-6h4v6h5V9.5"/>
                    </svg>
                    @break
                @case('announcements')
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592L5.436 14H4a2 2 0 01-2-2V9a2 2 0 012-2h1.436l2.147-5.832A1.76 1.76 0 0111 1.76v4.122zM19.5 8.5a4.5 4.5 0 010 7M16.5 10.5a2 2 0 010 3"/>
                    </svg>
                    @break
                @case('vacancies')
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6h4m-7 4h10M5 21h14a2 2 0 002-2V8a2 2 0 00-2-2h-3.5l-1-2h-5l-1 2H5a2 2 0 00-2 2v11a2 2 0 002 2z"/>
                    </svg>
                    @break
                @case('track')
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 11l3 3L22 4M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/>
                    </svg>
                    @break
            @endswitch
            <span>{{ $item['label'] }}</span>
        </a>
        @endforeach
    </nav>

    <div class="flex-1 md:flex-none"></div>

    {{-- Language toggle --}}
    @if(\App\Models\Setting::get('localization.show_language_switcher', true))
    <div class="hidden sm:flex items-center rounded-lg border border-gray-200 overflow-hidden text-xs font-semibold">
        <a href="{{ route('lang.switch', 'en') }}"
           class="px-3 py-1.5 transition {{ app()->getLocale() === 'en' ? 'bg-blue-600 text-white' : 'text-gray-500 hover:bg-gray-50' }}">EN</a>
        <a href="{{ route('lang.switch', 'am') }}"
           class="px-3 py-1.5 transition {{ app()->getLocale() === 'am' ? 'bg-blue-600 text-white' : 'text-gray-500 hover:bg-gray-50' }}">አማ</a>
    </div>
    @endif

    {{-- Auth buttons --}}
    <div class="hidden md:flex items-center gap-2">
        @auth
            @if(auth()->user()->hasRole('applicant'))
                <a href="{{ route('applicant.dashboard') }}"
                   class="text-sm font-medium text-gray-600 hover:text-blue-600 transition px-3 py-2 rounded-lg hover:bg-gray-100">
                    {{ __('menus.dashboard') }}
                </a>
                <form method="POST" action="{{ route('applicant.logout') }}" class="inline">
                    @csrf
                    <button type="submit"
                            class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-600 hover:bg-gray-50 transition">
                        {{ __('menus.logout') }}
                    </button>
                </form>
            @endif
        @else
            <a href="{{ route('applicant.login') }}"
               class="text-sm font-medium text-gray-600 hover:text-blue-600 transition px-3 py-2">
                {{ __('menus.login') }}
            </a>
            <a href="{{ route('applicant.register') }}"
               class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition shadow-sm">
                {{ __('menus.register') }}
            </a>
        @endauth
    </div>
</header>

{{-- ════════════════════════════════════ MOBILE DRAWER BACKDROP ══ --}}
<div x-show="drawerOpen" @click="drawerOpen = false"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 bg-black/50 z-40 md:hidden"
     style="display:none"></div>

{{-- ════════════════════════════════════════ MOBILE DRAWER PANEL ══ --}}
<div x-show="drawerOpen"
     x-transition:enter="transition ease-out duration-250"
     x-transition:enter-start="-translate-x-full"
     x-transition:enter-end="translate-x-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="translate-x-0"
     x-transition:leave-end="-translate-x-full"
     class="fixed top-0 left-0 bottom-0 w-72 bg-white z-50 flex flex-col md:hidden shadow-2xl"
     style="display:none">

    {{-- Drawer header --}}
    <div class="flex items-center justify-between px-4 h-16 border-b border-gray-100 shrink-0">
        <a href="{{ route('home') }}" @click="drawerOpen = false" class="flex items-center gap-2 min-w-0">
            @if($orgLogo)
                <img src="{{ Storage::url($orgLogo) }}" alt="{{ $orgName }}" class="h-7 w-auto object-contain shrink-0">
            @endif
            <span class="text-sm font-bold text-blue-700 truncate">{{ $orgName }}</span>
        </a>
        <button @click="drawerOpen = false"
                class="flex items-center justify-center h-8 w-8 rounded-lg text-gray-400 hover:bg-gray-100 transition shrink-0">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    {{-- Nav links --}}
    <nav class="flex-1 overflow-y-auto p-3 space-y-0.5">
        @foreach($publicNav as $item)
        @php $active = request()->routeIs($item['match']); @endphp
        <a href="{{ route($item['route']) }}" @click="drawerOpen = false"
           class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition
                  {{ $active ? 'bg-orange-500 text-white' : 'text-gray-700 hover:bg-gray-50' }}">
            @switch($item['icon'])
                @case('home')
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10.5L12 3l9 7.5M5 9.5V21h5v-6h4v6h5V9.5"/>
                    </svg>
                    @break
                @case('announcements')
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592L5.436 14H4a2 2 0 01-2-2V9a2 2 0 012-2h1.436l2.147-5.832A1.76 1.76 0 0111 1.76v4.122zM19.5 8.5a4.5 4.5 0 010 7M16.5 10.5a2 2 0 010 3"/>
                    </svg>
                    @break
                @case('vacancies')
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6h4m-7 4h10M5 21h14a2 2 0 002-2V8a2 2 0 00-2-2h-3.5l-1-2h-5l-1 2H5a2 2 0 00-2 2v11a2 2 0 002 2z"/>
                    </svg>
                    @break
                @case('track')
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 11l3 3L22 4M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/>
                    </svg>
                    @break
            @endswitch
            <span>{{ $item['label'] }}</span>
        </a>
        @endforeach
    </nav>

    {{-- Drawer footer --}}
    <div class="p-4 border-t border-gray-100 space-y-3 shrink-0">
        {{-- Language --}}
        @if(\App\Models\Setting::get('localization.show_language_switcher', true))
        <div class="flex items-center rounded-xl border border-gray-200 overflow-hidden text-xs font-semibold">
            <a href="{{ route('lang.switch', 'en') }}" @click="drawerOpen = false"
               class="flex-1 text-center py-2.5 transition {{ app()->getLocale() === 'en' ? 'bg-blue-600 text-white' : 'text-gray-500 hover:bg-gray-50' }}">EN</a>
            <a href="{{ route('lang.switch', 'am') }}" @click="drawerOpen = false"
               class="flex-1 text-center py-2.5 transition {{ app()->getLocale() === 'am' ? 'bg-blue-600 text-white' : 'text-gray-500 hover:bg-gray-50' }}">አማ</a>
        </div>
        @endif
        {{-- Auth --}}
        @auth
            @if(auth()->user()->hasRole('applicant'))
            <a href="{{ route('applicant.dashboard') }}" @click="drawerOpen = false"
               class="block w-full rounded-xl bg-blue-600 px-4 py-2.5 text-center text-sm font-medium text-white hover:bg-blue-700 transition">
                {{ __('menus.dashboard') }}
            </a>
            <form method="POST" action="{{ route('applicant.logout') }}">
                @csrf
                <button type="submit"
                        class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-medium text-red-600 hover:bg-red-50 transition">
                    {{ __('menus.logout') }}
                </button>
            </form>
            @endif
        @else
            <a href="{{ route('applicant.register') }}" @click="drawerOpen = false"
               class="block w-full rounded-xl bg-blue-600 px-4 py-2.5 text-center text-sm font-medium text-white hover:bg-blue-700 transition">
                {{ __('menus.register') }}
            </a>
            <a href="{{ route('applicant.login') }}" @click="drawerOpen = false"
               class="block w-full rounded-xl border border-gray-200 px-4 py-2.5 text-center text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                {{ __('menus.login') }}
            </a>
        @endauth
    </div>
</div>

{{-- ═══════════════════════════════════════════════ FLASH TOASTS ══ --}}
@if(session('success'))
<div class="fixed top-20 left-1/2 -translate-x-1/2 z-50 w-full max-w-sm px-4"
     x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 -translate-y-2"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">
    <div class="flex items-center gap-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 shadow-lg">
        <svg class="h-4 w-4 shrink-0 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        <span class="flex-1">{{ session('success') }}</span>
        <button @click="show = false" class="text-green-400 hover:text-green-600">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>
</div>
@endif

@if(session('error'))
<div class="fixed top-20 left-1/2 -translate-x-1/2 z-50 w-full max-w-sm px-4"
     x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 -translate-y-2"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">
    <div class="flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 shadow-lg">
        <svg class="h-4 w-4 shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span class="flex-1">{{ session('error') }}</span>
        <button @click="show = false" class="text-red-400 hover:text-red-600">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>
</div>
@endif

{{-- ══════════════════════════════════════════════ MAIN CONTENT ══ --}}
<main class="pt-16">
    @yield('content')
</main>

{{-- ═══════════════════════════════════════════════════ FOOTER ══ --}}
<footer class="bg-gray-900 text-gray-400 mt-16">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 gap-8 sm:grid-cols-2 lg:grid-cols-4">

            {{-- Org info --}}
            <div class="col-span-2 sm:col-span-1 lg:col-span-1">
                @if($orgLogo)
                <img src="{{ Storage::url($orgLogo) }}" alt="{{ $orgName }}" class="h-10 w-auto object-contain mb-3 opacity-80">
                @endif
                <h3 class="text-sm font-semibold text-white">{{ $orgName }}</h3>
                @php $addr = \App\Models\Setting::get('org.address', ''); @endphp
                @if($addr)
                <p class="mt-2 text-xs leading-relaxed">{{ $addr }}</p>
                @endif
            </div>

            {{-- Quick links --}}
            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-300 mb-4">{{ __('public.footer_quick_links') }}</h3>
                <ul class="space-y-2 text-sm">
                    <li>
                        <a href="{{ route('home') }}" class="inline-flex items-center gap-2 hover:text-white transition">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10.5L12 3l9 7.5M5 9.5V21h5v-6h4v6h5V9.5"/>
                            </svg>
                            <span>{{ __('menus.home') }}</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('vacancies.index') }}" class="inline-flex items-center gap-2 hover:text-white transition">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6h4m-7 4h10M5 21h14a2 2 0 002-2V8a2 2 0 00-2-2h-3.5l-1-2h-5l-1 2H5a2 2 0 00-2 2v11a2 2 0 002 2z"/>
                            </svg>
                            <span>{{ __('vacancies.job_vacancies') }}</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('announcements.index') }}" class="inline-flex items-center gap-2 hover:text-white transition">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592L5.436 14H4a2 2 0 01-2-2V9a2 2 0 012-2h1.436l2.147-5.832A1.76 1.76 0 0111 1.76v4.122zM19.5 8.5a4.5 4.5 0 010 7M16.5 10.5a2 2 0 010 3"/>
                            </svg>
                            <span>{{ __('menus.announcements') }}</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('track.show') }}" class="inline-flex items-center gap-2 hover:text-white transition">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 11l3 3L22 4M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/>
                            </svg>
                            <span>{{ __('menus.track_application') }}</span>
                        </a>
                    </li>
                </ul>
            </div>

            {{-- Applicant links --}}
            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-300 mb-4">{{ __('public.footer_applicant') }}</h3>
                <ul class="space-y-2 text-sm">
                    @auth
                        @if(auth()->user()->hasRole('applicant'))
                        <li>
                            <a href="{{ route('applicant.dashboard') }}" class="inline-flex items-center gap-2 hover:text-white transition">
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 13h6V4H4v9zm10 7h6v-9h-6v9zM4 20h6v-5H4v5zm10-11h6V4h-6v5z"/>
                                </svg>
                                <span>{{ __('menus.dashboard') }}</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('applicant.applications.index') }}" class="inline-flex items-center gap-2 hover:text-white transition">
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M7 3h7l5 5v13a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2z"/>
                                </svg>
                                <span>{{ __('menus.my_applications') }}</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('applicant.profile.show') }}" class="inline-flex items-center gap-2 hover:text-white transition">
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 7.5a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 21a7.5 7.5 0 0115 0"/>
                                </svg>
                                <span>{{ __('menus.profile') }}</span>
                            </a>
                        </li>
                        @endif
                    @else
                        <li>
                            <a href="{{ route('applicant.login') }}" class="inline-flex items-center gap-2 hover:text-white transition">
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4M10 17l5-5-5-5M15 12H3"/>
                                </svg>
                                <span>{{ __('public.footer_applicant_login') }}</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('applicant.register') }}" class="inline-flex items-center gap-2 hover:text-white transition">
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 20a6 6 0 00-12 0M12 10a4 4 0 100-8 4 4 0 000 8zm7-3v6m3-3h-6"/>
                                </svg>
                                <span>{{ __('public.footer_applicant_register') }}</span>
                            </a>
                        </li>
                    @endauth
                </ul>
            </div>

            {{-- Contact --}}
            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-300 mb-4">{{ __('public.footer_contact') }}</h3>
                <ul class="space-y-2 text-sm">
                    @php
                        $phone   = \App\Models\Setting::get('org.phone', '');
                        $email   = \App\Models\Setting::get('org.email', '');
                        $website = \App\Models\Setting::get('org.website', '');
                    @endphp
                    @if($phone)
                    <li class="flex items-start gap-2">
                        <svg class="h-4 w-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        <span>{{ $phone }}</span>
                    </li>
                    @endif
                    @if($email)
                    <li class="flex items-start gap-2">
                        <svg class="h-4 w-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <a href="mailto:{{ $email }}" class="hover:text-white transition break-all">{{ $email }}</a>
                    </li>
                    @endif
                    @if($website)
                    <li class="flex items-start gap-2">
                        <svg class="h-4 w-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/>
                        </svg>
                        <a href="{{ $website }}" target="_blank" rel="noopener noreferrer" class="hover:text-white transition break-all">{{ $website }}</a>
                    </li>
                    @endif
                </ul>
            </div>
        </div>

        {{-- Social links --}}
        @php
            $facebook = \App\Models\Setting::get('org.facebook', '');
            $twitter  = \App\Models\Setting::get('org.twitter', '');
            $linkedin = \App\Models\Setting::get('org.linkedin', '');
            $youtube  = \App\Models\Setting::get('org.youtube', '');
        @endphp
        @if($facebook || $twitter || $linkedin || $youtube)
        <div class="mt-8 flex flex-wrap gap-3">
            @if($linkedin)
            <a href="{{ $linkedin }}" target="_blank" rel="noopener noreferrer" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-gray-700 text-gray-400 transition hover:border-blue-500 hover:bg-blue-500 hover:text-white" aria-label="LinkedIn">
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
            </a>
            @endif
            @if($twitter)
            <a href="{{ $twitter }}" target="_blank" rel="noopener noreferrer" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-gray-700 text-gray-400 transition hover:border-gray-100 hover:bg-gray-100 hover:text-gray-950" aria-label="Twitter / X">
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26L22.827 21.75h-6.656l-5.214-6.817-5.966 6.817H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231 5.45-6.231zm-1.161 17.52h1.833L7.084 4.126H5.117L17.083 19.77z"/></svg>
            </a>
            @endif
            @if($facebook)
            <a href="{{ $facebook }}" target="_blank" rel="noopener noreferrer" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-gray-700 text-gray-400 transition hover:border-blue-600 hover:bg-blue-600 hover:text-white" aria-label="Facebook">
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878V14.89h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/></svg>
            </a>
            @endif
            @if($youtube)
            <a href="{{ $youtube }}" target="_blank" rel="noopener noreferrer" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-gray-700 text-gray-400 transition hover:border-red-600 hover:bg-red-600 hover:text-white" aria-label="YouTube">
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
            </a>
            @endif
        </div>
        @endif

        <div class="mt-8 border-t border-gray-800 pt-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-gray-600">
            <span>&copy; {{ date('Y') }} {{ $orgName }}. {{ \App\Models\Setting::get('org.footer_text', __('public.footer_rights')) }}</span>
            @if(\App\Models\Setting::get('localization.show_language_switcher', true))
            <div class="flex items-center rounded-lg border border-gray-700 overflow-hidden text-xs font-semibold">
                <a href="{{ route('lang.switch', 'en') }}"
                   class="px-3 py-1.5 transition {{ app()->getLocale() === 'en' ? 'bg-blue-600 text-white' : 'text-gray-500 hover:text-gray-300' }}">EN</a>
                <a href="{{ route('lang.switch', 'am') }}"
                   class="px-3 py-1.5 transition {{ app()->getLocale() === 'am' ? 'bg-blue-600 text-white' : 'text-gray-500 hover:text-gray-300' }}">አማ</a>
            </div>
            @endif
        </div>
    </div>
</footer>

</body>
</html>
