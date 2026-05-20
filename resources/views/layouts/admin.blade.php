<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="h-full locale-{{ app()->getLocale() }} lang-{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', __('menus.dashboard')) — {{ \App\Models\Setting::get('org.name', config('app.name')) }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{-- ── Prevent dark-mode flash before Alpine loads ── --}}
    <script>if(localStorage.getItem('theme')==='dark')document.documentElement.classList.add('dark');</script>
    {{-- ── Dynamic brand colors from Settings ── --}}
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
<body class="h-full bg-gray-50 font-sans text-gray-900 antialiased locale-{{ app()->getLocale() }} lang-{{ app()->getLocale() }}"
      x-data="adminShell()" @keydown.escape="sidebarOpen = false">

{{-- ── Mobile overlay ───────────────────────────────────────────── --}}
<div x-show="sidebarOpen"
     x-transition:enter="transition-opacity ease-linear duration-200"
     x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity ease-linear duration-200"
     x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm lg:hidden"
     @click="sidebarOpen = false" style="display:none"></div>

{{-- ── Sidebar ──────────────────────────────────────────────────── --}}
<aside
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    :style="`
        width: ${sidebarOpen || !sidebarCollapsed ? '16rem' : '4rem'};
        background: linear-gradient(170deg, var(--color-navy) 0%, color-mix(in srgb, var(--color-navy) 78%, black) 100%);
    `"
    class="fixed inset-y-0 left-0 z-50 flex flex-col lg:translate-x-0 transition-all duration-300 ease-in-out overflow-hidden">

    {{-- ── Brand header ─────────────────────────────────────────── --}}
    <div class="flex h-16 shrink-0 items-center gap-3 border-b border-white/10 px-3">
        {{-- Logo / initials (always visible) --}}
        @php $orgLogo = \App\Models\Setting::get('org.logo', ''); @endphp
        @if($orgLogo)
        <img src="{{ Storage::url($orgLogo) }}" alt=""
             class="h-9 w-9 shrink-0 rounded-xl object-cover ring-2 ring-white/20 shadow-md">
        @else
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-accent text-sm font-bold text-white shadow-lg shadow-black/20 ring-1 ring-white/10">
            {{ mb_substr(\App\Models\Setting::get('org.name', config('app.name')), 0, 2) }}
        </div>
        @endif

        {{-- Org name + label (hidden when desktop collapsed) --}}
        <div class="flex-1 min-w-0 overflow-hidden transition-all duration-300"
             :class="sidebarCollapsed ? 'opacity-0 w-0' : 'opacity-100'">
            <p class="truncate text-[13px] font-semibold text-white leading-tight whitespace-nowrap">
                {{ \App\Models\Setting::get('org.name', config('app.name')) }}
            </p>
            <p class="text-[11px] text-blue-300/80 whitespace-nowrap">{{ __('menus.admin_panel') }}</p>
        </div>

    </div>

    {{-- ── Navigation ───────────────────────────────────────────── --}}
    <nav class="flex-1 overflow-y-auto overflow-x-hidden px-2 py-3 space-y-0.5">
        @php
        $authUser = auth()->user();
        $nav = [];
        $canManageExamInterview = $authUser->hasAnyRole(['super_admin', 'admin', 'hr_manager', 'hr_officer', 'exam_officer', 'interview_officer'])
            || $authUser->hasAnyPermission(['exams.view', 'interviews.view', 'exams.record-results', 'interviews.record-results']);

        $nav[] = ['route' => 'admin.dashboard', 'label' => __('menus.dashboard'), 'match' => 'admin.dashboard',
            'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'];

        if ($authUser->hasAnyPermission(['vacancies.view', 'applications.view', 'screening.view']) || $canManageExamInterview)
            $nav[] = 'recruitment';
        if ($authUser->hasPermissionTo('vacancies.view')) {
            $nav[] = ['route' => 'admin.vacancies.index', 'label' => __('menus.vacancies'), 'match' => 'admin.vacancies.*',
                'icon' => 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'];
            $nav[] = ['route' => 'admin.announcements.index', 'label' => __('menus.announcements'), 'match' => 'admin.announcements.*',
                'icon' => 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z'];
        }
        if ($authUser->hasPermissionTo('applications.view')) {
            $nav[] = ['route' => 'admin.applications.index', 'label' => __('menus.applications'), 'match' => 'admin.applications.*',
                'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'];
            $nav[] = ['route' => 'admin.applicants.index', 'label' => __('menus.applicants'), 'match' => 'admin.applicants.*',
                'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'];
        }
        if ($authUser->hasPermissionTo('screening.view')) {
            $nav[] = 'screening';
            $nav[] = ['route' => 'admin.screening.index', 'label' => __('menus.screening'), 'match' => 'admin.screening.index',
                'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'];
            $nav[] = ['route' => 'admin.screening.passed', 'label' => __('menus.passed_applicants'), 'match' => 'admin.screening.passed',
                'icon' => 'M5 13l4 4L19 7'];
            $nav[] = ['route' => 'admin.screening.failed', 'label' => __('menus.failed_applicants'), 'match' => 'admin.screening.failed',
                'icon' => 'M6 18L18 6M6 6l12 12'];
        }
        if ($canManageExamInterview) {
            $nav[] = 'exams';
            $nav[] = ['route' => 'admin.schedules.index', 'label' => __('menus.schedules'), 'match' => 'admin.schedules.*',
                'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'];
            $nav[] = ['route' => 'admin.final-results.index', 'label' => __('menus.final_results'), 'match' => 'admin.final-results.*',
                'icon' => 'M9 12l2 2 4-4M7 4h10a2 2 0 012 2v12a2 2 0 01-2 2H7a2 2 0 01-2-2V6a2 2 0 012-2z'];
        }
        if ($authUser->hasAnyRole(['super_admin', 'admin', 'hr_manager']) || $authUser->hasAnyPermission(['notifications.view', 'notifications.templates.manage', 'notifications.send'])) {
            $nav[] = 'notifications';
            $nav[] = ['route' => 'admin.notification-templates.index', 'label' => __('menus.notification_templates'), 'match' => 'admin.notification-templates.*',
                'icon' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9'];
        }
        if ($authUser->hasPermissionTo('reports.view')) {
            $nav[] = 'reports';
            $nav[] = ['route' => 'admin.reports.index', 'label' => __('menus.reports'), 'match' => 'admin.reports.*',
                'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'];
        }
        if ($authUser->hasAnyPermission(['users.view', 'roles.view', 'permissions.view'])) {
            $nav[] = 'access';
            if ($authUser->hasPermissionTo('users.view'))
                $nav[] = ['route' => 'admin.users.index', 'label' => __('menus.users'), 'match' => 'admin.users.*',
                    'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'];
            if ($authUser->hasAnyPermission(['roles.view', 'permissions.view']))
                $nav[] = ['route' => 'admin.roles.index', 'label' => __('menus.roles'), 'match' => 'admin.roles.*',
                    'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'];
        }
        if ($authUser->hasAnyPermission(['settings.view', 'audit.view'])) {
            $nav[] = 'system';
            if ($authUser->hasPermissionTo('settings.view')) {
                $nav[] = ['route' => 'admin.settings.index', 'label' => __('menus.settings'), 'match' => 'admin.settings.*',
                    'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z'];
                $nav[] = ['route' => 'admin.hero-sliders.index', 'label' => __('menus.hero_slider'), 'match' => 'admin.hero-sliders.*',
                    'icon' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'];
            }
            if ($authUser->hasPermissionTo('audit.view'))
                $nav[] = ['route' => 'admin.audit-logs.index', 'label' => __('menus.audit_logs'), 'match' => 'admin.audit-logs.*',
                    'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'];
        }

        $groupLabels = [
            'recruitment'   => __('menus.recruitment'),
            'screening'     => __('menus.screening'),
            'exams'         => __('menus.exams_interviews'),
            'notifications' => __('menus.notifications'),
            'reports'       => __('menus.reports'),
            'access'        => __('menus.access_control'),
            'system'        => __('menus.system'),
        ];
        @endphp

        @foreach($nav as $item)
            @if(is_string($item))
            {{-- Group separator --}}
            <div class="overflow-hidden px-1 transition-all duration-300"
                 :class="sidebarCollapsed ? 'mt-3 mb-1' : 'mt-5 mb-1.5'">
                <p class="text-[10px] font-bold uppercase tracking-widest text-blue-300/60 whitespace-nowrap transition-all duration-200"
                   :class="sidebarCollapsed ? 'opacity-0 h-0 overflow-hidden' : 'opacity-100 h-auto pl-2'">
                    {{ $groupLabels[$item] ?? $item }}
                </p>
                <div class="mx-1 h-px bg-white/10 transition-opacity duration-200"
                     :class="sidebarCollapsed ? 'opacity-100' : 'opacity-0 h-0 overflow-hidden'"></div>
            </div>
            @else
            @php $active = request()->routeIs($item['match']); @endphp
            <a href="{{ route($item['route']) }}"
               :title="sidebarCollapsed ? '{{ addslashes($item['label']) }}' : null"
               :class="sidebarCollapsed ? 'justify-center px-2' : 'justify-start gap-3 px-3'"
               class="group relative flex items-center rounded-xl py-2.5 text-sm font-medium transition-all duration-200
                      {{ $active
                            ? 'bg-white/15 text-white shadow-sm ring-1 ring-white/10'
                            : 'text-blue-100/80 hover:bg-white/10 hover:text-white' }}">

                {{-- Active left accent bar --}}
                @if($active)
                <span class="absolute left-0 inset-y-2.5 w-0.5 rounded-r-full bg-accent transition-all duration-200"
                      :class="sidebarCollapsed ? 'opacity-0' : 'opacity-100'"></span>
                @endif

                {{-- Icon --}}
                <svg class="h-5 w-5 shrink-0 transition-colors duration-200
                            {{ $active ? 'text-white' : 'text-blue-300 group-hover:text-white' }}"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $item['icon'] }}"/>
                </svg>

                {{-- Label --}}
                <span class="truncate whitespace-nowrap overflow-hidden transition-all duration-200"
                      :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-xs opacity-100'">
                    {{ $item['label'] }}
                </span>

                {{-- Active indicator dot --}}
                @if($active)
                <span class="ml-auto shrink-0 h-1.5 w-1.5 rounded-full bg-accent/80 transition-all duration-200"
                      :class="sidebarCollapsed ? 'hidden' : 'block'"></span>
                @endif
            </a>
            @endif
        @endforeach
    </nav>

    {{-- ── User footer ──────────────────────────────────────────── --}}
    <div class="shrink-0 border-t border-white/10 p-2.5">
        <div class="flex items-center gap-3 rounded-xl px-2 py-2 hover:bg-white/8 transition-colors duration-200"
             :class="sidebarCollapsed ? 'justify-center' : ''">
            {{-- Avatar --}}
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white/15 text-xs font-semibold text-white ring-1 ring-white/20">
                {{ mb_substr(auth()->user()?->name ?? 'A', 0, 2) }}
            </div>
            {{-- Name + role (hidden when collapsed) --}}
            <div class="min-w-0 flex-1 overflow-hidden transition-all duration-200"
                 :class="sidebarCollapsed ? 'w-0 opacity-0' : 'w-auto opacity-100'">
                <p class="truncate text-[13px] font-medium text-white whitespace-nowrap leading-tight">
                    {{ auth()->user()?->name }}
                </p>
                <p class="truncate text-[11px] text-blue-300/80 whitespace-nowrap">
                    {{ auth()->user()?->roles->first()?->name }}
                </p>
            </div>
            {{-- Logout button (hidden when collapsed) --}}
            <div class="shrink-0 transition-all duration-200" :class="sidebarCollapsed ? 'w-0 opacity-0 overflow-hidden' : 'opacity-100'">
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" title="{{ __('menus.logout') }}"
                            class="rounded-lg p-1.5 text-blue-300/70 hover:bg-white/10 hover:text-red-400 transition-colors duration-200">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </div>
</aside>

{{-- ── Main wrapper — shifts right to accommodate sidebar ─────────── --}}
<div class="flex flex-col min-h-full transition-all duration-300 ease-in-out"
     :class="sidebarCollapsed ? 'lg:pl-16' : 'lg:pl-64'">

    {{-- ── Topbar ──────────────────────────────────────────────── --}}
    <header class="sticky top-0 z-30 flex h-16 shrink-0 items-center gap-3 border-b border-gray-200 bg-white/95 backdrop-blur px-4 shadow-[0_1px_3px_rgba(0,0,0,0.06)] sm:px-6">

        {{-- Hamburger (mobile) --}}
        <button @click="sidebarOpen = !sidebarOpen"
                class="lg:hidden -ml-1 rounded-lg p-2 text-gray-500 hover:bg-brand-muted hover:text-brand transition">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        {{-- Sidebar collapse/expand toggle (desktop only) --}}
        <button @click="toggleSidebar()"
                :title="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                class="hidden lg:flex -ml-1 rounded-lg p-2 text-gray-500 hover:bg-brand-muted hover:text-brand transition">
            <svg class="h-5 w-5 transition-transform duration-300 ease-in-out"
                 :class="sidebarCollapsed ? 'rotate-180' : ''"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
            </svg>
        </button>

        {{-- Page title / breadcrumb --}}
        <div class="flex flex-1 items-center gap-2 min-w-0">
            <div class="h-5 w-0.5 rounded-full bg-accent hidden sm:block shrink-0"></div>
            <h1 class="truncate text-sm font-semibold text-gray-800">@yield('title', __('menus.dashboard'))</h1>
            @hasSection('breadcrumb')
            <span class="text-gray-300 shrink-0">/</span>
            @yield('breadcrumb')
            @endif
        </div>

        {{-- Right actions --}}
        <div class="flex items-center gap-1.5">

            {{-- Dark / Light toggle --}}
            <button @click="toggleDark()" title="Toggle dark mode"
                    class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700 transition">
                <svg x-show="!darkMode" class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                </svg>
                <svg x-show="darkMode" class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </button>

            {{-- Locale switcher --}}
            @if(\App\Models\Setting::get('localization.show_language_switcher', true))
            <div class="hidden sm:flex items-center gap-0.5 rounded-lg border border-gray-200 bg-gray-50 p-0.5 text-xs">
                <a href="{{ route('lang.switch', 'en') }}"
                   class="rounded-md px-2.5 py-1 font-medium transition-all
                          {{ app()->getLocale() === 'en' ? 'bg-brand text-white shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">EN</a>
                <a href="{{ route('lang.switch', 'am') }}"
                   class="rounded-md px-2.5 py-1 font-medium transition-all
                          {{ app()->getLocale() === 'am' ? 'bg-brand text-white shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">አማ</a>
            </div>
            @endif

            {{-- User dropdown --}}
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" @click.outside="open = false"
                        class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-sm text-gray-700 hover:border-brand/30 hover:bg-brand-muted transition">
                    <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand text-[10px] font-bold text-white">
                        {{ mb_substr(auth()->user()?->name ?? 'A', 0, 2) }}
                    </div>
                    <span class="hidden sm:block max-w-28 truncate font-medium">{{ auth()->user()?->name }}</span>
                    <svg class="h-3.5 w-3.5 text-gray-400 transition-transform duration-200" :class="open && 'rotate-180'"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div x-show="open"
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                     class="absolute right-0 mt-2 w-52 origin-top-right rounded-xl border border-gray-100 bg-white py-1 shadow-lg ring-1 ring-black/5"
                     style="display:none">
                    <div class="border-b border-gray-100 px-4 py-3">
                        <p class="text-xs font-semibold text-gray-800 truncate">{{ auth()->user()?->name }}</p>
                        <p class="text-xs text-gray-400 truncate">{{ auth()->user()?->email }}</p>
                    </div>
                    <a href="{{ route('admin.profile.edit') }}"
                       class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition">
                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        {{ __('messages.edit_profile') }}
                    </a>
                    <div class="border-t border-gray-100"></div>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit"
                                class="flex w-full items-center gap-2.5 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            {{ __('menus.logout') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    {{-- ── Flash messages ──────────────────────────────────────── --}}
    @if(session('success'))
    <div class="mx-4 mt-4 flex items-start gap-3 rounded-xl border-l-4 border-green-500 bg-white px-4 py-3.5 shadow-card sm:mx-6" role="alert">
        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-green-100">
            <svg class="h-4 w-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <div class="min-w-0 flex-1 pt-0.5">
            <p class="text-sm font-medium text-gray-800">{{ session('success') }}</p>
        </div>
    </div>
    @endif

    @if(session('error'))
    <div class="mx-4 mt-4 flex items-start gap-3 rounded-xl border-l-4 border-red-500 bg-white px-4 py-3.5 shadow-card sm:mx-6" role="alert">
        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-red-100">
            <svg class="h-4 w-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div class="min-w-0 flex-1 pt-0.5">
            <p class="text-sm font-medium text-gray-800">{{ session('error') }}</p>
        </div>
    </div>
    @endif

    @if(session('warning'))
    <div class="mx-4 mt-4 flex items-start gap-3 rounded-xl border-l-4 border-amber-500 bg-white px-4 py-3.5 shadow-card sm:mx-6" role="alert">
        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-amber-100">
            <svg class="h-4 w-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>
        <div class="min-w-0 flex-1 pt-0.5">
            <p class="text-sm font-medium text-gray-800">{{ session('warning') }}</p>
        </div>
    </div>
    @endif

    @if($errors->any())
    <div class="mx-4 mt-4 rounded-xl border-l-4 border-red-500 bg-white px-4 py-3.5 shadow-card sm:mx-6">
        <p class="mb-1.5 text-sm font-semibold text-red-700">{{ __('messages.fix_errors') }}</p>
        <ul class="space-y-0.5 text-sm text-red-600 list-disc list-inside">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Main content --}}
    <main class="flex-1 px-4 py-6 sm:px-6">
        @yield('content')
    </main>
</div>

<script>
function adminShell() {
    return {
        sidebarOpen: false,
        sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
        darkMode: localStorage.getItem('theme') === 'dark',
        toggleDark() {
            this.darkMode = !this.darkMode;
            localStorage.setItem('theme', this.darkMode ? 'dark' : 'light');
            document.documentElement.classList.toggle('dark', this.darkMode);
        },
        toggleSidebar() {
            this.sidebarCollapsed = !this.sidebarCollapsed;
            localStorage.setItem('sidebarCollapsed', this.sidebarCollapsed);
        },
    };
}
</script>

@stack('scripts')
</body>
</html>
