<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="ltr" class="scroll-smooth locale-{{ app()->getLocale() }} lang-{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name')) &mdash; {{ \App\Models\Setting::get('org.name', config('app.name')) }}</title>
    <meta name="description" content="@yield('meta_description', 'Job Vacancy Announcement System')">
    @include('partials.favicon')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @include('partials.admin-theme')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="public-site flex min-h-screen flex-col bg-[#F5F7F7] text-gray-900 antialiased locale-{{ app()->getLocale() }} lang-{{ app()->getLocale() }}"
      x-data="{ drawerOpen: false, scrolled: false }"
      x-init="scrolled = window.scrollY > 8"
      @scroll.window.passive="scrolled = window.scrollY > 8"
      @keydown.escape.window="drawerOpen = false"
      :class="drawerOpen && 'overflow-hidden'">

@php
    $orgLogo = \App\Models\Setting::get('org.logo', '');
    $orgName = \App\Models\Setting::get('org.name', config('app.name'));
    $orgPhone = \App\Models\Setting::get('org.phone', '');
    $showLangSwitcher = \App\Models\Setting::get('localization.show_language_switcher', true);
    $locale = app()->getLocale();
    $publicNav = [
        ['route' => 'home',                'label' => __('menus.home'),              'match' => 'home',            'icon' => 'home'],
        ['route' => 'vacancies.index',     'label' => __('public.find_jobs'),        'match' => 'vacancies.*',     'icon' => 'briefcase'],
        ['route' => 'announcements.index', 'label' => __('menus.announcements'),     'match' => 'announcements.*', 'icon' => 'megaphone'],
        ['route' => 'track.show',          'label' => __('menus.track_application'), 'match' => 'track.*',         'icon' => 'track'],
    ];
    $dashboardRoute = auth()->check()
        ? (auth()->user()->canAccessAdminArea() ? route('admin.dashboard') : route('applicant.dashboard'))
        : null;
    $languages = ['en' => 'EN', 'am' => 'አማ'];
    $languages = array_intersect_key($languages, array_flip((array) \App\Models\Setting::get('app.available_locales', ['en', 'am'])));
@endphp

<a href="#main-content"
   class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-70 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-brand focus:shadow-lg">
    {{ __('public.skip_to_content') }}
</a>

{{-- ══════════════════════════════════════════════ UTILITY BAR ══ --}}
<div class="hidden bg-ink text-[13px] text-white/75 sm:block">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-2 sm:px-6 lg:px-8">
        <p class="flex min-w-0 items-center gap-2">
            <x-public.icon name="shield-check" class="h-3.5 w-3.5 shrink-0 text-brand-muted" />
            <span class="truncate">{{ __('public.official_portal', ['org' => $orgName]) }}</span>
        </p>
        <div class="flex shrink-0 items-center gap-5">
            @if($orgPhone)
            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $orgPhone) }}" class="hidden items-center gap-1.5 transition hover:text-white md:inline-flex">
                <x-public.icon name="phone" class="h-3.5 w-3.5" />
                {{ $orgPhone }}
            </a>
            @endif
            @if($showLangSwitcher)
            <div class="flex items-center rounded-md bg-ink-soft p-0.5 text-xs font-semibold" role="group" aria-label="{{ __('public.language') }}">
                @foreach($languages as $code => $label)
                <a href="{{ route('lang.switch', $code) }}" lang="{{ $code }}"
                   @if($locale === $code) aria-current="true" @endif
                   class="rounded px-2.5 py-1 transition {{ $locale === $code ? 'bg-white text-ink' : 'text-white/70 hover:text-white' }}">{{ $label }}</a>
                @endforeach
            </div>
            @endif
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════ HEADER ══ --}}
<header class="sticky top-0 z-50 border-b border-gray-200 bg-white transition-shadow"
        :class="scrolled && 'shadow-sm'">
    <div class="mx-auto flex h-16 max-w-7xl items-center gap-6 px-4 sm:px-6 lg:h-18 lg:px-8">

        {{-- Logo + org name --}}
        <a href="{{ route('home') }}" class="flex min-w-0 shrink items-center gap-3">
            @if($orgLogo)
                <img src="{{ Storage::url($orgLogo) }}" alt="" class="h-10 w-auto shrink-0 object-contain">
            @else
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand text-white">
                    <x-public.icon name="briefcase" class="h-5 w-5" />
                </span>
            @endif
            <span class="flex min-w-0 flex-col leading-tight">
                <span class="max-w-44 truncate text-[15px] font-extrabold text-gray-900 sm:max-w-60 lg:max-w-xs">{{ $orgName }}</span>
                <span class="hidden text-xs font-medium text-gray-500 sm:block">{{ __('public.careers_tagline') }}</span>
            </span>
        </a>

        {{-- Desktop nav --}}
        <nav class="hidden h-full flex-1 items-stretch gap-1 lg:flex" aria-label="{{ __('public.main_navigation') }}">
            @foreach($publicNav as $item)
            @php $active = request()->routeIs($item['match']); @endphp
            <a href="{{ route($item['route']) }}"
               @if($active) aria-current="page" @endif
               class="inline-flex items-center whitespace-nowrap border-b-[3px] px-3 pt-[3px] text-[15px] font-semibold transition
                      {{ $active ? 'border-brand text-brand' : 'border-transparent text-gray-600 hover:border-gray-300 hover:text-gray-900' }}">
                {{ $item['label'] }}
            </a>
            @endforeach
        </nav>

        <div class="flex-1 lg:hidden"></div>

        {{-- Auth buttons --}}
        <div class="hidden items-center gap-2 lg:flex">
            @auth
                <a href="{{ $dashboardRoute }}"
                   class="inline-flex h-11 items-center gap-2 rounded-xl bg-brand px-4 text-[15px] font-bold text-white transition hover:bg-brand-dark">
                    <x-public.icon name="dashboard" />
                    {{ __('menus.dashboard') }}
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" title="{{ __('menus.logout') }}"
                            class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-gray-200 text-gray-500 transition hover:bg-gray-50 hover:text-red-600">
                        <x-public.icon name="logout" />
                        <span class="sr-only">{{ __('menus.logout') }}</span>
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}"
                   class="inline-flex h-11 items-center rounded-xl border border-gray-300 px-4 text-[15px] font-bold text-gray-900 transition hover:bg-gray-50">
                    {{ __('public.sign_in') }}
                </a>
                <a href="{{ route('applicant.register') }}"
                   class="inline-flex h-11 items-center rounded-xl bg-brand px-4 text-[15px] font-bold text-white transition hover:bg-brand-dark">
                    {{ __('public.create_account') }}
                </a>
            @endauth
        </div>

        {{-- Mobile: language + menu --}}
        @if($showLangSwitcher)
        <div class="flex items-center rounded-lg border border-gray-200 p-0.5 text-xs font-semibold sm:hidden" role="group" aria-label="{{ __('public.language') }}">
            @foreach($languages as $code => $label)
            <a href="{{ route('lang.switch', $code) }}" lang="{{ $code }}"
               class="rounded-md px-2 py-1.5 {{ $locale === $code ? 'bg-ink text-white' : 'text-gray-600' }}">{{ $label }}</a>
            @endforeach
        </div>
        @endif
        <button type="button" @click="drawerOpen = true"
                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-gray-700 transition hover:bg-gray-100 lg:hidden"
                :aria-expanded="drawerOpen.toString()" aria-controls="mobile-drawer">
            <x-public.icon name="menu" class="h-6 w-6" />
            <span class="sr-only">{{ __('public.menu') }}</span>
        </button>
    </div>
</header>

{{-- ════════════════════════════════════════════ MOBILE DRAWER ══ --}}
<div x-show="drawerOpen" x-cloak
     x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     @click="drawerOpen = false"
     class="fixed inset-0 z-50 bg-gray-950/50 backdrop-blur-sm lg:hidden"></div>

<div id="mobile-drawer" x-show="drawerOpen" x-cloak
     x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
     x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
     class="fixed inset-y-0 right-0 z-50 flex w-80 max-w-[85vw] flex-col bg-white shadow-2xl lg:hidden"
     role="dialog" aria-modal="true" aria-label="{{ __('public.menu') }}">

    <div class="flex h-16 shrink-0 items-center justify-between border-b border-gray-100 px-4">
        <span class="truncate text-sm font-extrabold text-gray-900">{{ $orgName }}</span>
        <button type="button" @click="drawerOpen = false"
                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-gray-500 transition hover:bg-gray-100">
            <x-public.icon name="x" class="h-5 w-5" />
            <span class="sr-only">{{ __('public.close') }}</span>
        </button>
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto p-3" aria-label="{{ __('public.main_navigation') }}">
        @foreach($publicNav as $item)
        @php $active = request()->routeIs($item['match']); @endphp
        <a href="{{ route($item['route']) }}"
           @if($active) aria-current="page" @endif
           class="flex items-center gap-3 rounded-xl px-4 py-3 text-base font-semibold transition
                  {{ $active ? 'bg-brand-muted text-brand' : 'text-gray-700 hover:bg-gray-50' }}">
            <x-public.icon :name="$item['icon']" class="h-5 w-5 {{ $active ? '' : 'text-gray-400' }}" />
            <span>{{ $item['label'] }}</span>
        </a>
        @endforeach
    </nav>

    <div class="shrink-0 space-y-3 border-t border-gray-100 p-4">
        @auth
            <a href="{{ $dashboardRoute }}"
               class="flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-brand px-4 text-sm font-bold text-white transition hover:bg-brand-dark">
                <x-public.icon name="dashboard" />
                {{ __('menus.dashboard') }}
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="flex h-12 w-full items-center justify-center gap-2 rounded-xl border border-gray-200 px-4 text-sm font-bold text-red-600 transition hover:bg-red-50">
                    <x-public.icon name="logout" />
                    {{ __('menus.logout') }}
                </button>
            </form>
        @else
            <a href="{{ route('applicant.register') }}"
               class="flex h-12 w-full items-center justify-center rounded-xl bg-brand px-4 text-sm font-bold text-white transition hover:bg-brand-dark">
                {{ __('public.create_account') }}
            </a>
            <a href="{{ route('login') }}"
               class="flex h-12 w-full items-center justify-center rounded-xl border border-gray-300 px-4 text-sm font-bold text-gray-900 transition hover:bg-gray-50">
                {{ __('public.sign_in') }}
            </a>
        @endauth
    </div>
</div>

{{-- ═══════════════════════════════════════════════ FLASH TOASTS ══ --}}
@foreach(['success' => ['check-circle', 'border-green-200 bg-white text-green-800', 'text-green-500', 4000], 'error' => ['alert', 'border-red-200 bg-white text-red-800', 'text-red-500', 6000]] as $flashKey => [$flashIcon, $flashClasses, $flashIconColor, $flashTimeout])
@if(session($flashKey))
<div class="fixed right-4 top-20 z-60 w-[calc(100%-2rem)] max-w-sm"
     x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, {{ $flashTimeout }})"
     x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0"
     x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     role="{{ $flashKey === 'error' ? 'alert' : 'status' }}">
    <div class="flex items-start gap-3 rounded-xl border px-4 py-3 text-sm shadow-lg {{ $flashClasses }}">
        <x-public.icon :name="$flashIcon" class="mt-0.5 h-5 w-5 {{ $flashIconColor }}" />
        <span class="flex-1">{{ session($flashKey) }}</span>
        <button type="button" @click="show = false" class="text-gray-400 hover:text-gray-600">
            <x-public.icon name="x" />
            <span class="sr-only">{{ __('public.close') }}</span>
        </button>
    </div>
</div>
@endif
@endforeach

{{-- ══════════════════════════════════════════════ MAIN CONTENT ══ --}}
<main id="main-content" class="flex-1" tabindex="-1">
    @yield('content')
</main>

{{-- ═══════════════════════════════════════════════════ FOOTER ══ --}}
@php
    $addr     = \App\Models\Setting::get('org.address', '');
    $email    = \App\Models\Setting::get('org.email', '');
    $website  = \App\Models\Setting::get('org.website', '');
    $socials  = array_filter([
        'LinkedIn'    => [\App\Models\Setting::get('org.linkedin', ''), 'M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z'],
        'Twitter / X' => [\App\Models\Setting::get('org.twitter', ''), 'M18.244 2.25h3.308l-7.227 8.26L22.827 21.75h-6.656l-5.214-6.817-5.966 6.817H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231 5.45-6.231zm-1.161 17.52h1.833L7.084 4.126H5.117L17.083 19.77z'],
        'Facebook'    => [\App\Models\Setting::get('org.facebook', ''), 'M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878V14.89h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z'],
        'YouTube'     => [\App\Models\Setting::get('org.youtube', ''), 'M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z'],
    ], fn ($s) => filled($s[0]));
    $footerLink = 'transition hover:text-white';
    $footerHeading = 'mb-4 text-xs font-extrabold uppercase tracking-wider text-white';
@endphp
<footer class="bg-ink text-[15px] text-white/70">
    <div class="mx-auto max-w-7xl px-4 pb-8 pt-14 sm:px-6 lg:px-8">
        <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-12">

            {{-- Org info --}}
            <div class="sm:col-span-2 lg:col-span-5">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-3">
                    @if($orgLogo)
                        <img src="{{ Storage::url($orgLogo) }}" alt="" class="h-10 w-auto rounded-lg bg-white/95 object-contain p-1">
                    @endif
                    <span class="text-lg font-extrabold text-white">{{ $orgName }}</span>
                </a>
                <p class="mt-4 max-w-sm leading-relaxed">{{ __('public.footer_tagline') }}</p>

                @if(!empty($socials))
                <div class="mt-6 flex flex-wrap gap-2">
                    @foreach($socials as $label => [$url, $path])
                    <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $label }}"
                       class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-white/5 text-white/70 ring-1 ring-white/10 transition hover:bg-white/15 hover:text-white">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $path }}"/></svg>
                    </a>
                    @endforeach
                </div>
                @endif
            </div>

            {{-- Jobs --}}
            <div class="lg:col-span-2">
                <h2 class="{{ $footerHeading }}">{{ __('public.footer_quick_links') }}</h2>
                <ul class="space-y-2.5">
                    @foreach($publicNav as $item)
                    <li><a href="{{ route($item['route']) }}" class="{{ $footerLink }}">{{ $item['label'] }}</a></li>
                    @endforeach
                </ul>
            </div>

            {{-- Applicant links --}}
            <div class="lg:col-span-2">
                <h2 class="{{ $footerHeading }}">{{ __('public.footer_applicant') }}</h2>
                <ul class="space-y-2.5">
                    @auth
                        <li><a href="{{ $dashboardRoute }}" class="{{ $footerLink }}">{{ __('menus.dashboard') }}</a></li>
                        @if(auth()->user()->hasRole('applicant'))
                        <li><a href="{{ route('applicant.applications.index') }}" class="{{ $footerLink }}">{{ __('menus.my_applications') }}</a></li>
                        <li><a href="{{ route('applicant.profile.show') }}" class="{{ $footerLink }}">{{ __('menus.profile') }}</a></li>
                        @endif
                    @else
                        <li><a href="{{ route('applicant.register') }}" class="{{ $footerLink }}">{{ __('public.create_account') }}</a></li>
                        <li><a href="{{ route('login') }}" class="{{ $footerLink }}">{{ __('public.sign_in') }}</a></li>
                    @endauth
                </ul>
            </div>

            {{-- Contact --}}
            <div class="lg:col-span-3">
                <h2 class="{{ $footerHeading }}">{{ __('public.footer_contact') }}</h2>
                <ul class="space-y-3">
                    @if($orgPhone)
                    <li class="flex items-start gap-2.5">
                        <x-public.icon name="phone" class="mt-1 h-4 w-4 text-white/40" />
                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $orgPhone) }}" class="{{ $footerLink }}">{{ $orgPhone }}</a>
                    </li>
                    @endif
                    @if($email)
                    <li class="flex items-start gap-2.5">
                        <x-public.icon name="mail" class="mt-1 h-4 w-4 text-white/40" />
                        <a href="mailto:{{ $email }}" class="break-all {{ $footerLink }}">{{ $email }}</a>
                    </li>
                    @endif
                    @if($addr)
                    <li class="flex items-start gap-2.5">
                        <x-public.icon name="map-pin" class="mt-1 h-4 w-4 text-white/40" />
                        <span>{{ $addr }}</span>
                    </li>
                    @endif
                    @if($website)
                    <li class="flex items-start gap-2.5">
                        <x-public.icon name="globe" class="mt-1 h-4 w-4 text-white/40" />
                        <a href="{{ $website }}" target="_blank" rel="noopener noreferrer" class="break-all {{ $footerLink }}">{{ preg_replace('#^https?://#', '', rtrim($website, '/')) }}</a>
                    </li>
                    @endif
                </ul>
            </div>
        </div>

        <div class="mt-12 flex flex-col items-center justify-between gap-4 border-t border-white/10 pt-6 text-[13px] text-white/50 sm:flex-row">
            <span>&copy; {{ date('Y') }} {{ $orgName }}. {{ \App\Models\Setting::get('org.footer_text', __('public.footer_rights')) }}</span>
            @if($showLangSwitcher)
            <div class="flex items-center rounded-lg bg-white/5 p-0.5 font-semibold ring-1 ring-white/10" role="group" aria-label="{{ __('public.language') }}">
                @foreach($languages as $code => $label)
                <a href="{{ route('lang.switch', $code) }}" lang="{{ $code }}"
                   class="rounded-md px-2.5 py-1 transition {{ $locale === $code ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white' }}">{{ $label }}</a>
                @endforeach
            </div>
            @endif
        </div>
    </div>
</footer>

@stack('scripts')
</body>
</html>
