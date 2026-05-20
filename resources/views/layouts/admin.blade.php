<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="h-full locale-{{ app()->getLocale() }} lang-{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', __('menus.dashboard')) — {{ \App\Models\Setting::get('org.name', config('app.name')) }}</title>
    @include('partials.favicon')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>if(localStorage.getItem('theme')==='dark')document.documentElement.classList.add('dark');</script>
    @php
        $themePrimary = \App\Models\Setting::get('appearance.primary_color', '#1A56DB');
        $themeSidebar = \App\Models\Setting::get('appearance.sidebar_color', '#1E3A8A');
        $themeAccent  = \App\Models\Setting::get('appearance.accent_color',  '#FF6B2B');
        $adminLogoSize = min(max((int) \App\Models\Setting::get('appearance.logo_size', 36), 24), 72);
    @endphp
    <style>
    /* ── Brand CSS vars ─────────────────────────────────────────── */
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

    /* ── Sidebar design tokens — light mode (default) ───────────── */
    :root {
        --sb-text:          #1e293b;
        --sb-muted:         #64748b;
        --sb-dim:           #94a3b8;
        --sb-border:        rgba(0,0,0,.08);
        --sb-hover:         rgba(0,0,0,.05);
        --sb-active-bg:     color-mix(in srgb, var(--color-brand) 10%, white);
        --sb-active-text:   var(--color-brand);
        --sb-active-icon:   var(--color-brand);
        --sb-inactive-icon: #94a3b8;
        --sb-hover-icon:    #475569;
        --sb-line:          rgba(0,0,0,.08);
        --sb-badge-bg:      rgba(0,0,0,.06);
        --sb-badge-text:    #475569;
        --sb-footer-bg:     rgba(0,0,0,.03);
        --sb-action:        #94a3b8;
        --sb-radial:        transparent;
    }

    /* ── Sidebar design tokens — dark mode ──────────────────────── */
    html.dark {
        --sb-text:          rgba(255,255,255,.92);
        --sb-muted:         rgba(255,255,255,.55);
        --sb-dim:           rgba(255,255,255,.35);
        --sb-border:        rgba(255,255,255,.08);
        --sb-hover:         rgba(255,255,255,.07);
        --sb-active-bg:     rgba(255,255,255,.1);
        --sb-active-text:   #ffffff;
        --sb-active-icon:   #ffffff;
        --sb-inactive-icon: rgba(255,255,255,.38);
        --sb-hover-icon:    rgba(255,255,255,.85);
        --sb-line:          rgba(255,255,255,.1);
        --sb-badge-bg:      rgba(255,255,255,.1);
        --sb-badge-text:    rgba(255,255,255,.7);
        --sb-footer-bg:     rgba(255,255,255,.04);
        --sb-action:        rgba(255,255,255,.42);
        --sb-radial:        rgba(255,255,255,.055);
    }

    /* ── Sidebar scrollbar ──────────────────────────────────────── */
    .sidebar-nav::-webkit-scrollbar       { width: 3px; }
    .sidebar-nav::-webkit-scrollbar-track { background: transparent; }
    .sidebar-nav::-webkit-scrollbar-thumb { background: var(--sb-line); border-radius: 99px; }
    .sidebar-nav::-webkit-scrollbar-thumb:hover { background: var(--sb-muted); }

    /* ── Collapsed tooltip ──────────────────────────────────────── */
    .nav-tooltip {
        pointer-events: none;
        position: fixed;
        left: 4.5rem;
        z-index: 200;
        white-space: nowrap;
        padding: .35rem .75rem;
        background: #0f172a;
        color: #f8fafc;
        font-size: .75rem;
        font-weight: 500;
        border-radius: .5rem;
        box-shadow: 0 4px 12px rgba(0,0,0,.35);
        opacity: 0;
        transform: translateX(-4px);
        transition: opacity .15s ease, transform .15s ease;
    }
    .nav-item:hover .nav-tooltip { opacity: 1; transform: translateX(0); }

    /* ── Nav item states ────────────────────────────────────────── */
    .nav-item:hover        { background: var(--sb-hover) !important; }
    .nav-active            { background: var(--sb-active-bg); color: var(--sb-active-text); }
    .nav-inactive          { color: var(--sb-muted); }
    .nav-icon-active       { color: var(--sb-active-icon); }
    .nav-icon-inactive     { color: var(--sb-inactive-icon); }

    /* ── Topbar design tokens — light mode ──────────────────────── */
    :root {
        --tb-bg:            rgba(255,255,255,.97);
        --tb-border:        rgba(0,0,0,.07);
        --tb-text:          #111827;
        --tb-muted:         #6b7280;
        --tb-dim:           #9ca3af;
        --tb-hover:         rgba(0,0,0,.05);
        --tb-search-bg:     #f3f4f6;
        --tb-search-border: rgba(0,0,0,.09);
        --tb-search-text:   #9ca3af;
    }

    /* ── Topbar design tokens — dark mode ───────────────────────── */
    html.dark {
        --tb-bg:            rgba(10,17,34,.97);
        --tb-border:        rgba(255,255,255,.07);
        --tb-text:          rgba(255,255,255,.88);
        --tb-muted:         rgba(255,255,255,.45);
        --tb-dim:           rgba(255,255,255,.28);
        --tb-hover:         rgba(255,255,255,.07);
        --tb-search-bg:     rgba(255,255,255,.06);
        --tb-search-border: rgba(255,255,255,.09);
        --tb-search-text:   rgba(255,255,255,.3);
    }

    /* ── Topbar btn base ────────────────────────────────────────── */
    .tb-btn {
        align-items: center; justify-content: center;
        height: 2rem; width: 2rem; border-radius: .5rem;
        color: var(--tb-muted); transition: background .12s ease, color .12s ease;
    }
    .tb-btn:hover { background: var(--tb-hover); color: var(--tb-text); }

    /* ── Locale switcher active/inactive ────────────────────────── */
    .locale-active   { background: var(--color-brand); color: #fff; }
    .locale-inactive { color: var(--tb-muted); }
    .locale-inactive:hover { color: var(--tb-text); }

    [data-admin-page-frame] {
        transition: opacity .16s ease, transform .16s ease;
    }

    [data-admin-page-frame].is-loading {
        opacity: .38;
        pointer-events: none;
        transform: translateY(4px);
    }

    [data-admin-progress] {
        opacity: 0;
        transform: scaleX(0);
        transform-origin: left;
        transition: opacity .2s ease, transform .2s ease;
    }

    [data-admin-progress].is-loading {
        opacity: 1;
        transform: scaleX(1);
    }
    </style>
</head>
<body class="h-full bg-gray-50 font-sans text-gray-900 antialiased locale-{{ app()->getLocale() }} lang-{{ app()->getLocale() }}"
      x-data="adminShell()"
      @keydown.escape="sidebarOpen = false; searchOpen = false"
      @keydown.window.ctrl.k.prevent="openSearch()"
      @keydown.window.meta.k.prevent="openSearch()">

{{-- ════════════════════════════════════════════════════════════
     Mobile overlay
════════════════════════════════════════════════════════════ --}}
<div x-show="sidebarOpen"
     x-transition:enter="transition-opacity ease-linear duration-200"
     x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity ease-linear duration-200"
     x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-40 bg-black/60 backdrop-blur-sm lg:hidden"
     @click="sidebarOpen = false" style="display:none"></div>

{{-- ════════════════════════════════════════════════════════════
     Sidebar
     Background switches via Alpine darkMode:
       light → white + right-border shadow
       dark  → 3-stop navy gradient + drop shadow
════════════════════════════════════════════════════════════ --}}
<aside
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    :style="`
        width: ${sidebarOpen || !sidebarCollapsed ? '16rem' : '4rem'};
        background: ${darkMode
            ? 'linear-gradient(160deg, color-mix(in srgb,var(--color-navy) 112%,white) 0%, var(--color-navy) 42%, color-mix(in srgb,var(--color-navy) 78%,black) 100%)'
            : 'white'};
        box-shadow: ${darkMode
            ? '4px 0 28px rgba(0,0,0,.22)'
            : '1px 0 0 rgba(0,0,0,.08), 3px 0 12px rgba(0,0,0,.04)'};
    `"
    class="fixed inset-y-0 left-0 z-50 flex flex-col lg:translate-x-0 transition-all duration-300 ease-out overflow-hidden">

    {{-- Radial highlight (dark mode only) ─────────────────────── --}}
    <div class="pointer-events-none absolute inset-0 z-0"
         style="background: radial-gradient(ellipse 200px 180px at 8% 0%, var(--sb-radial) 0%, transparent 70%);"></div>

    {{-- ════════════════════════════════════════════════════════════
         Brand header
    ════════════════════════════════════════════════════════════ --}}
    <div class="relative z-10 flex h-16 shrink-0 items-center gap-3 px-3.5"
         style="border-bottom: 1px solid var(--sb-border);">

        {{-- Logo mark --}}
        @php $orgLogo = \App\Models\Setting::get('org.logo', ''); @endphp
        @if($orgLogo)
        <img src="{{ Storage::url($orgLogo) }}" alt=""
             class="shrink-0 object-contain"
             style="width: {{ $adminLogoSize }}px; height: {{ $adminLogoSize }}px;">
        @else
        <div class="flex shrink-0 items-center justify-center text-sm font-bold"
             style="width: {{ $adminLogoSize }}px; height: {{ $adminLogoSize }}px; color: var(--color-brand);">
            {{ mb_substr(\App\Models\Setting::get('org.name', config('app.name')), 0, 2) }}
        </div>
        @endif

        {{-- Org name + "Admin" badge --}}
        <div class="flex-1 min-w-0 overflow-hidden transition-all duration-300"
             :class="sidebarCollapsed ? 'opacity-0 w-0' : 'opacity-100'">
            <p class="truncate text-[13px] font-semibold leading-tight whitespace-nowrap"
               style="color: var(--sb-text);">
                {{ \App\Models\Setting::get('org.name', config('app.name')) }}
            </p>
            <div class="flex items-center gap-1.5 mt-0.5">
                <span class="inline-flex items-center rounded px-1.5 py-px text-[10px] font-semibold uppercase tracking-wider"
                      style="background: var(--sb-badge-bg); color: var(--sb-badge-text); letter-spacing:.07em;">
                    Admin
                </span>
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════════
         Navigation
    ════════════════════════════════════════════════════════════ --}}
    <nav class="sidebar-nav relative z-10 flex-1 overflow-y-auto overflow-x-hidden py-3 px-2 space-y-0.5">
        @php
        $authUser = auth()->user();
        $nav = [];
        $canManageExamInterview =
            $authUser->hasAnyRole(['super_admin','admin','hr_manager','hr_officer','exam_officer','interview_officer'])
            || $authUser->hasAnyPermission(['exams.view','interviews.view','exams.record-results','interviews.record-results']);

        $nav[] = [
            'route' => 'admin.dashboard', 'label' => __('menus.dashboard'),
            'match' => 'admin.dashboard',
            'icon'  => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
        ];

        if ($authUser->hasAnyPermission(['vacancies.view','applications.view','screening.view']) || $canManageExamInterview)
            $nav[] = 'recruitment';
        if ($authUser->hasPermissionTo('vacancies.view')) {
            $nav[] = ['route'=>'admin.vacancies.index',    'label'=>__('menus.vacancies'),    'match'=>'admin.vacancies.*',
                'icon'=>'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'];
            $nav[] = ['route'=>'admin.announcements.index','label'=>__('menus.announcements'),'match'=>'admin.announcements.*',
                'icon'=>'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z'];
        }
        if ($authUser->hasPermissionTo('applications.view')) {
            $nav[] = ['route'=>'admin.applications.index','label'=>__('menus.applications'),'match'=>'admin.applications.*',
                'icon'=>'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'];
            $nav[] = ['route'=>'admin.applicants.index',  'label'=>__('menus.applicants'),  'match'=>'admin.applicants.*',
                'icon'=>'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'];
        }
        if ($authUser->hasPermissionTo('screening.view')) {
            $nav[] = 'screening';
            $nav[] = ['route'=>'admin.screening.index', 'label'=>__('menus.screening'),         'match'=>'admin.screening.index',
                'icon'=>'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'];
            $nav[] = ['route'=>'admin.screening.passed','label'=>__('menus.passed_applicants'), 'match'=>'admin.screening.passed',
                'icon'=>'M5 13l4 4L19 7'];
            $nav[] = ['route'=>'admin.screening.failed','label'=>__('menus.failed_applicants'),'match'=>'admin.screening.failed',
                'icon'=>'M6 18L18 6M6 6l12 12'];
        }
        if ($canManageExamInterview) {
            $nav[] = 'exams';
            $nav[] = ['route'=>'admin.schedules.index',    'label'=>__('menus.schedules'),    'match'=>'admin.schedules.*',
                'icon'=>'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'];
            $nav[] = ['route'=>'admin.final-results.index','label'=>__('menus.final_results'),'match'=>'admin.final-results.*',
                'icon'=>'M9 12l2 2 4-4M7 4h10a2 2 0 012 2v12a2 2 0 01-2 2H7a2 2 0 01-2-2V6a2 2 0 012-2z'];
        }
        if ($authUser->hasAnyRole(['super_admin','admin','hr_manager']) || $authUser->hasAnyPermission(['notifications.view','notifications.templates.manage','notifications.send'])) {
            $nav[] = 'notifications';
            $nav[] = ['route'=>'admin.notification-templates.index','label'=>__('menus.notification_templates'),'match'=>'admin.notification-templates.*',
                'icon'=>'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9'];
        }
        if ($authUser->hasPermissionTo('reports.view')) {
            $nav[] = 'reports';
            $nav[] = ['route'=>'admin.reports.index','label'=>__('menus.reports'),'match'=>'admin.reports.*',
                'icon'=>'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'];
        }
        if ($authUser->hasAnyPermission(['users.view','roles.view','permissions.view'])) {
            $nav[] = 'access';
            if ($authUser->hasPermissionTo('users.view'))
                $nav[] = ['route'=>'admin.users.index','label'=>__('menus.users'),'match'=>'admin.users.*',
                    'icon'=>'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'];
            if ($authUser->hasAnyPermission(['roles.view','permissions.view']))
                $nav[] = ['route'=>'admin.roles.index','label'=>__('menus.roles'),'match'=>'admin.roles.*',
                    'icon'=>'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'];
        }
        if ($authUser->hasAnyPermission(['settings.view','audit.view'])) {
            $nav[] = 'system';
            if ($authUser->hasPermissionTo('settings.view')) {
                $nav[] = ['route'=>'admin.settings.index',   'label'=>__('menus.settings'),   'match'=>'admin.settings.*',
                    'icon'=>'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z'];
                $nav[] = ['route'=>'admin.hero-sliders.index','label'=>__('menus.hero_slider'),'match'=>'admin.hero-sliders.*',
                    'icon'=>'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'];
            }
            if ($authUser->hasPermissionTo('audit.view'))
                $nav[] = ['route'=>'admin.audit-logs.index','label'=>__('menus.audit_logs'),'match'=>'admin.audit-logs.*',
                    'icon'=>'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'];
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

        {{-- ── Section group header ───────────────────────────────── --}}
        @if(is_string($item))
        <div class="overflow-hidden transition-all duration-300"
             :class="sidebarCollapsed ? 'mt-2 mb-1' : 'mt-5 mb-1.5'">
            {{-- Expanded: ruled label --}}
            <div class="flex items-center gap-2 px-1 transition-all duration-200"
                 :class="sidebarCollapsed ? 'opacity-0 h-0 overflow-hidden' : 'opacity-100'">
                <span class="block h-px flex-1 rounded-full" style="background:var(--sb-line)"></span>
                <span class="text-[9.5px] font-bold uppercase tracking-widest whitespace-nowrap"
                      style="color:var(--sb-dim)">{{ $groupLabels[$item] ?? $item }}</span>
                <span class="block h-px flex-1 rounded-full" style="background:var(--sb-line)"></span>
            </div>
            {{-- Collapsed: single rule --}}
            <div class="mx-2 h-px rounded-full transition-opacity duration-200"
                 style="background:var(--sb-line)"
                 :class="sidebarCollapsed ? 'opacity-100' : 'opacity-0 h-0 overflow-hidden'"></div>
        </div>

        {{-- ── Nav link ────────────────────────────────────────────── --}}
        @else
        @php $active = request()->routeIs($item['match']); @endphp
        <a href="{{ route($item['route']) }}"
           class="nav-item group relative flex items-center rounded-lg text-sm font-medium transition-all duration-150 ease-out {{ $active ? 'nav-active' : 'nav-inactive' }}"
           :class="sidebarCollapsed ? 'justify-center py-2.5 px-0' : 'gap-3 py-2 px-3'">

            {{-- Active: left accent rail with glow --}}
            @if($active)
            <span class="absolute inset-y-0 left-0 w-0.75 rounded-full"
                  style="background: var(--color-accent); box-shadow: 0 0 8px color-mix(in srgb, var(--color-accent) 70%, transparent);"
                  :class="sidebarCollapsed ? 'opacity-0' : 'opacity-100'"></span>
            @else
            {{-- Hover: ghost left rail --}}
            <span class="absolute inset-y-1 left-0 w-0.5 rounded-full opacity-0 group-hover:opacity-50 transition-opacity duration-150"
                  style="background: var(--color-accent);"></span>
            @endif

            {{-- Icon --}}
            <svg class="shrink-0 transition-colors duration-150 ease-out {{ $active ? 'nav-icon-active' : 'nav-icon-inactive' }}"
                 style="width:17px;height:17px;"
                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                 viewBox="0 0 24 24">
                <path d="{{ $item['icon'] }}"/>
            </svg>

            {{-- Label --}}
            <span class="truncate whitespace-nowrap overflow-hidden transition-all duration-200 ease-out"
                  :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-xs opacity-100'">
                {{ $item['label'] }}
            </span>

            {{-- Active dot --}}
            @if($active)
            <span class="ml-auto shrink-0 h-1.5 w-1.5 rounded-full"
                  style="background:var(--color-accent)"
                  :class="sidebarCollapsed ? 'hidden' : 'block'"></span>
            @endif

            {{-- Collapsed tooltip --}}
            <span class="nav-tooltip" :class="sidebarCollapsed ? '' : 'hidden!'">
                {{ $item['label'] }}
            </span>
        </a>
        @endif

        @endforeach
    </nav>

    {{-- ════════════════════════════════════════════════════════════
         User footer
    ════════════════════════════════════════════════════════════ --}}
    <div class="relative z-10 shrink-0 p-2.5" style="border-top: 1px solid var(--sb-border);">
        @php
            $userName    = auth()->user()?->name ?? 'User';
            $userRole    = auth()->user()?->roles->first()?->name ?? '';
            $firstName   = explode(' ', trim($userName))[0] ?? $userName;
            $lastName    = count(explode(' ', trim($userName))) > 1 ? explode(' ', trim($userName))[count(explode(' ', trim($userName)))-1] : '';
            $userInitials = mb_strtoupper(mb_substr($firstName,0,1) . mb_substr($lastName,0,1));
        @endphp

        <div class="flex items-center gap-2.5 rounded-xl p-2 transition-colors duration-150"
             style="background: var(--sb-footer-bg);"
             :class="sidebarCollapsed ? 'justify-center' : ''">

            {{-- Avatar --}}
            <div class="relative shrink-0">
                <div class="flex h-8 w-8 items-center justify-center rounded-full text-[11px] font-bold text-white"
                     style="background: linear-gradient(135deg, var(--color-brand) 0%, color-mix(in srgb,var(--color-brand) 60%,var(--color-accent)) 100%);
                            box-shadow: 0 0 0 2px var(--sb-line), 0 2px 6px rgba(0,0,0,.2);">
                    {{ $userInitials ?: mb_strtoupper(mb_substr($userName,0,2)) }}
                </div>
                <span class="absolute -bottom-0.5 -right-0.5 h-2.5 w-2.5 rounded-full border-2 bg-emerald-400"
                      style="border-color: inherit;"></span>
            </div>

            {{-- Name + role --}}
            <div class="min-w-0 flex-1 overflow-hidden transition-all duration-200"
                 :class="sidebarCollapsed ? 'w-0 opacity-0' : 'w-auto opacity-100'">
                <p class="truncate text-[12.5px] font-semibold whitespace-nowrap leading-tight"
                   style="color: var(--sb-text);">{{ $userName }}</p>
                @if($userRole)
                <p class="truncate text-[10.5px] font-medium whitespace-nowrap mt-0.5"
                   style="color: var(--color-accent); opacity:.9;">
                    {{ ucfirst(str_replace('_', ' ', $userRole)) }}
                </p>
                @endif
            </div>

            {{-- Action buttons --}}
            <div class="flex shrink-0 items-center gap-0.5 transition-all duration-200"
                 :class="sidebarCollapsed ? 'w-0 opacity-0 overflow-hidden' : 'opacity-100'">
                <a href="{{ route('admin.profile.edit') }}"
                   title="{{ __('messages.edit_profile') }}"
                   class="flex h-7 w-7 items-center justify-center rounded-lg transition-colors duration-150 hover:opacity-100"
                   style="color: var(--sb-action);"
                   onmouseover="this.style.background='var(--sb-hover)'"
                   onmouseout="this.style.background='transparent'">
                    <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </a>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" title="{{ __('menus.logout') }}"
                            class="flex h-7 w-7 items-center justify-center rounded-lg transition-colors duration-150"
                            style="color: var(--sb-action);"
                            onmouseover="this.style.background='rgba(239,68,68,.1)';this.style.color='#ef4444'"
                            onmouseout="this.style.background='transparent';this.style.color='var(--sb-action)'">
                        <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </div>
</aside>

{{-- ════════════════════════════════════════════════════════════
     Main content wrapper
════════════════════════════════════════════════════════════ --}}
<div class="flex flex-col min-h-full transition-all duration-300 ease-out"
     :class="sidebarCollapsed ? 'lg:pl-16' : 'lg:pl-64'">
    <div data-admin-progress class="fixed left-0 right-0 top-0 z-[80] h-0.5"
         style="background: linear-gradient(90deg, var(--color-brand), var(--color-accent));"></div>

    {{-- ════════════════════════════════════════════════════════════
         Topbar — three-zone: LEFT | CENTER | RIGHT
    ════════════════════════════════════════════════════════════ --}}
    <header class="sticky top-0 z-30 h-14 shrink-0 flex items-center gap-2 px-4 sm:px-5"
            style="background:var(--tb-bg); border-bottom:1px solid var(--tb-border);
                   backdrop-filter:blur(14px); -webkit-backdrop-filter:blur(14px);
                   box-shadow:0 1px 0 rgba(0,0,0,.05), 0 2px 8px rgba(0,0,0,.04);">

        {{-- ── LEFT: toggles + breadcrumb ─────────────────────────── --}}
        <div class="flex items-center gap-1 shrink-0">

            {{-- Mobile hamburger --}}
            <button @click="sidebarOpen = !sidebarOpen"
                    class="tb-btn lg:hidden"
                    aria-label="Open menu">
                <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            {{-- Desktop sidebar collapse toggle --}}
            <button @click="toggleSidebar()"
                    :title="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                    class="tb-btn hidden lg:flex"
                    aria-label="Toggle sidebar">
                <svg class="h-4.5 w-4.5 transition-transform duration-300 ease-out"
                     :class="sidebarCollapsed ? 'rotate-180' : ''"
                     fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
                </svg>
            </button>

            {{-- Separator --}}
            <div class="hidden sm:block h-5 w-px mx-0.5 shrink-0" style="background:var(--tb-border)"></div>

            {{-- Page title / breadcrumb --}}
            <div class="flex items-center gap-1.5 min-w-0">
                <h1 class="text-sm font-semibold truncate" style="color:var(--tb-text)">
                    @yield('title', __('menus.dashboard'))
                </h1>
                @hasSection('breadcrumb')
                <svg class="h-3.5 w-3.5 shrink-0" style="color:var(--tb-dim)"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                @yield('breadcrumb')
                @endif
            </div>
        </div>

        {{-- ── CENTER: command-palette search trigger ──────────────── --}}
        <div class="flex-1 flex justify-center px-3 sm:px-6">

            {{-- Full pill on sm+ --}}
            <button @click="openSearch()"
                    class="hidden sm:flex items-center gap-2.5 h-9 w-full max-w-xs xl:max-w-sm rounded-xl px-3 text-sm transition-all duration-150"
                    style="background:var(--tb-search-bg); border:1px solid var(--tb-search-border);"
                    onmouseover="this.style.borderColor='var(--color-brand)'"
                    onmouseout="this.style.borderColor='var(--tb-search-border)'">
                <svg class="h-4 w-4 shrink-0" style="color:var(--tb-search-text)"
                     fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <span class="flex-1 text-left" style="color:var(--tb-search-text)">Search...</span>
                <kbd class="hidden lg:inline-flex items-center gap-0.5 rounded-md px-1.5 py-0.5 text-[10px] font-medium"
                     style="background:var(--tb-hover); color:var(--tb-dim); border:1px solid var(--tb-search-border);">
                    <span>⌘</span><span>K</span>
                </kbd>
            </button>

            {{-- Icon-only on mobile --}}
            <button @click="openSearch()"
                    class="sm:hidden tb-btn"
                    aria-label="Search">
                <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </button>
        </div>

        {{-- ── RIGHT: notifications + dark + locale + user ────────── --}}
        <div class="flex items-center gap-0.5 shrink-0">

            {{-- Notification bell --}}
            <div class="relative" x-data="{ notifOpen: false }">
                <button @click="notifOpen = !notifOpen" @click.outside="notifOpen = false"
                        class="tb-btn relative"
                        aria-label="Notifications">
                    <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    {{-- Red dot placeholder badge --}}
                    <span class="absolute top-1.5 right-1.5 h-2 w-2 rounded-full"
                          style="background:#ef4444; border:1.5px solid var(--tb-bg);"></span>
                </button>

                <div x-show="notifOpen"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="absolute right-0 mt-2 w-80 rounded-2xl overflow-hidden"
                     style="background:var(--tb-bg); border:1px solid var(--tb-border);
                            box-shadow:0 12px 40px rgba(0,0,0,.18); display:none;">
                    <div class="px-4 py-3" style="border-bottom:1px solid var(--tb-border);">
                        <p class="text-sm font-semibold" style="color:var(--tb-text)">Notifications</p>
                    </div>
                    <div class="py-10 text-center">
                        <svg class="mx-auto h-8 w-8 mb-2.5" style="color:var(--tb-dim)"
                             fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <p class="text-sm" style="color:var(--tb-muted)">No notifications yet</p>
                    </div>
                </div>
            </div>

            {{-- Dark / light toggle --}}
            <button @click="toggleDark()" title="Toggle dark mode" class="tb-btn">
                <svg x-show="!darkMode" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                </svg>
                <svg x-show="darkMode" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2"
                     viewBox="0 0 24 24" style="display:none">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </button>

            {{-- Locale switcher --}}
            @if(\App\Models\Setting::get('localization.show_language_switcher', true))
            <div class="hidden sm:flex items-center gap-0.5 rounded-lg p-0.5 text-xs mx-0.5"
                 style="border:1px solid var(--tb-border); background:var(--tb-search-bg);">
                <a href="{{ route('lang.switch', 'en') }}"
                   class="rounded-md px-2.5 py-1 font-medium transition-all duration-150 {{ app()->getLocale() === 'en' ? 'locale-active' : 'locale-inactive' }}">EN</a>
                <a href="{{ route('lang.switch', 'am') }}"
                   class="rounded-md px-2.5 py-1 font-medium transition-all duration-150 {{ app()->getLocale() === 'am' ? 'locale-active' : 'locale-inactive' }}">አማ</a>
            </div>
            @endif

            {{-- Separator --}}
            <div class="hidden sm:block h-5 w-px mx-1 shrink-0" style="background:var(--tb-border)"></div>

            {{-- User dropdown --}}
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" @click.outside="open = false"
                        class="flex items-center gap-2 rounded-xl pl-1.5 pr-2.5 py-1 text-sm transition-all duration-150"
                        style="border:1px solid var(--tb-border);"
                        onmouseover="this.style.background='var(--tb-hover)'"
                        onmouseout="this.style.background='transparent'">
                    <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[10px] font-bold text-white"
                         style="background:linear-gradient(135deg,var(--color-brand),color-mix(in srgb,var(--color-brand) 60%,var(--color-accent)))">
                        {{ $userInitials ?: mb_strtoupper(mb_substr($userName,0,2)) }}
                    </div>
                    <span class="hidden sm:block max-w-24 truncate text-[13px] font-medium"
                          style="color:var(--tb-text)">{{ $firstName }}</span>
                    <svg class="h-3.5 w-3.5 transition-transform duration-200" :class="open && 'rotate-180'"
                         style="color:var(--tb-dim)"
                         fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div x-show="open"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="absolute right-0 mt-2 w-56 origin-top-right rounded-2xl overflow-hidden"
                     style="background:var(--tb-bg); border:1px solid var(--tb-border);
                            box-shadow:0 12px 40px rgba(0,0,0,.18); display:none;">
                    <div class="px-4 py-3.5" style="border-bottom:1px solid var(--tb-border);">
                        <p class="text-[12.5px] font-semibold truncate" style="color:var(--tb-text)">{{ $userName }}</p>
                        <p class="text-xs truncate mt-0.5" style="color:var(--tb-muted)">{{ auth()->user()?->email }}</p>
                    </div>
                    <div class="py-1">
                        <a href="{{ route('admin.profile.edit') }}"
                           class="flex items-center gap-2.5 px-4 py-2.5 text-sm transition-colors duration-100"
                           style="color:var(--tb-text);"
                           onmouseover="this.style.background='var(--tb-hover)'"
                           onmouseout="this.style.background='transparent'">
                            <svg class="h-4 w-4 shrink-0" style="color:var(--tb-dim)"
                                 fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            {{ __('messages.edit_profile') }}
                        </a>
                    </div>
                    <div class="py-1" style="border-top:1px solid var(--tb-border);">
                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <button type="submit"
                                    class="flex w-full items-center gap-2.5 px-4 py-2.5 text-sm transition-colors duration-100"
                                    style="color:#ef4444;"
                                    onmouseover="this.style.background='rgba(239,68,68,.08)'"
                                    onmouseout="this.style.background='transparent'">
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor"
                                     stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                                {{ __('menus.logout') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </header>

    {{-- ════════════════════════════════════════════════════════════
         Command palette / global search modal
    ════════════════════════════════════════════════════════════ --}}
    <div x-show="searchOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-start justify-center pt-[10vh] px-4"
         @click.self="searchOpen = false"
         style="background:rgba(0,0,0,.55); backdrop-filter:blur(4px); display:none;">

        <div x-show="searchOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95 -translate-y-2"
             class="w-full max-w-lg rounded-2xl overflow-hidden"
             style="background:var(--tb-bg); border:1px solid var(--tb-border);
                    box-shadow:0 28px 70px rgba(0,0,0,.28);">

            {{-- Search input row --}}
            <div class="flex items-center gap-3 px-4" style="border-bottom:1px solid var(--tb-border);">
                <svg class="h-4.5 w-4.5 shrink-0" style="color:var(--tb-muted)"
                     fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input x-ref="searchInput"
                       x-model="searchQuery"
                       type="text"
                       placeholder="Search anything..."
                       class="flex-1 bg-transparent py-4 text-sm outline-none"
                       style="color:var(--tb-text); caret-color:var(--color-brand);"
                       @keydown.escape.stop="searchOpen = false">
                <kbd class="shrink-0 rounded-md px-1.5 py-0.5 text-[10px] font-medium"
                     style="background:var(--tb-hover); color:var(--tb-dim); border:1px solid var(--tb-search-border);">ESC</kbd>
            </div>

            {{-- Quick links --}}
            <div class="p-2">
                <p class="px-2 pt-1 pb-1.5 text-[10.5px] font-semibold uppercase tracking-wider"
                   style="color:var(--tb-dim)">Quick links</p>
                @php
                $searchLinks = [
                    ['route'=>'admin.dashboard',         'label'=>__('menus.dashboard'),
                     'icon'=>'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                    ['route'=>'admin.vacancies.index',   'label'=>__('menus.vacancies'),
                     'icon'=>'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
                    ['route'=>'admin.applications.index','label'=>__('menus.applications'),
                     'icon'=>'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                    ['route'=>'admin.settings.index',    'label'=>__('menus.settings'),
                     'icon'=>'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z'],
                ];
                @endphp
                @foreach($searchLinks as $link)
                <a href="{{ route($link['route']) }}"
                   @click="searchOpen = false"
                   class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition-colors duration-100"
                   style="color:var(--tb-text);"
                   onmouseover="this.style.background='var(--tb-hover)'"
                   onmouseout="this.style.background='transparent'">
                    <svg class="h-4 w-4 shrink-0" style="color:var(--tb-muted)"
                         fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $link['icon'] }}"/>
                    </svg>
                    {{ $link['label'] }}
                    <svg class="ml-auto h-3.5 w-3.5 shrink-0 opacity-0 group-hover:opacity-100" style="color:var(--tb-dim)"
                         fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
                @endforeach
            </div>

            {{-- Footer --}}
            <div class="flex items-center gap-4 px-4 py-2.5"
                 style="border-top:1px solid var(--tb-border); background:var(--tb-search-bg);">
                <span class="flex items-center gap-1.5 text-[11px]" style="color:var(--tb-dim)">
                    <kbd class="rounded px-1.5 py-0.5 text-[10px]"
                         style="border:1px solid var(--tb-border); background:var(--tb-hover);">↵</kbd>
                    to open
                </span>
                <span class="flex items-center gap-1.5 text-[11px]" style="color:var(--tb-dim)">
                    <kbd class="rounded px-1.5 py-0.5 text-[10px]"
                         style="border:1px solid var(--tb-border); background:var(--tb-hover);">ESC</kbd>
                    to close
                </span>
            </div>
        </div>
    </div>

    {{-- ── Flash messages ──────────────────────────────────────────── --}}
    <div id="admin-page-frame" data-admin-page-frame>
    @if(session('success'))
    <div class="mx-4 mt-4 flex items-start gap-3 rounded-xl border border-green-100 bg-green-50 px-4 py-3.5 sm:mx-6" role="alert">
        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-green-100">
            <svg class="h-3.5 w-3.5 text-green-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <p class="text-sm text-green-800 pt-0.5 flex-1">{{ session('success') }}</p>
    </div>
    @endif
    @if(session('error'))
    <div class="mx-4 mt-4 flex items-start gap-3 rounded-xl border border-red-100 bg-red-50 px-4 py-3.5 sm:mx-6" role="alert">
        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-red-100">
            <svg class="h-3.5 w-3.5 text-red-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </div>
        <p class="text-sm text-red-800 pt-0.5 flex-1">{{ session('error') }}</p>
    </div>
    @endif
    @if(session('warning'))
    <div class="mx-4 mt-4 flex items-start gap-3 rounded-xl border border-amber-100 bg-amber-50 px-4 py-3.5 sm:mx-6" role="alert">
        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-amber-100">
            <svg class="h-3.5 w-3.5 text-amber-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>
        <p class="text-sm text-amber-800 pt-0.5 flex-1">{{ session('warning') }}</p>
    </div>
    @endif
    @if($errors->any())
    <div class="mx-4 mt-4 rounded-xl border border-red-100 bg-red-50 px-4 py-3.5 sm:mx-6">
        <p class="mb-1.5 text-sm font-semibold text-red-700">{{ __('messages.fix_errors') }}</p>
        <ul class="space-y-0.5 text-sm text-red-600 list-disc list-inside">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <main class="flex-1 px-4 py-5 sm:px-6">
        @yield('content')
    </main>
    </div>
</div>

<script>
function adminShell() {
    return {
        sidebarOpen: false,
        sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
        darkMode: localStorage.getItem('theme') === 'dark',
        searchOpen: false,
        searchQuery: '',
        toggleDark() {
            this.darkMode = !this.darkMode;
            localStorage.setItem('theme', this.darkMode ? 'dark' : 'light');
            document.documentElement.classList.toggle('dark', this.darkMode);
        },
        toggleSidebar() {
            this.sidebarCollapsed = !this.sidebarCollapsed;
            localStorage.setItem('sidebarCollapsed', this.sidebarCollapsed);
        },
        openSearch() {
            this.searchOpen = true;
            this.$nextTick(() => this.$refs.searchInput?.focus());
        },
    };
}
</script>

<script>
(() => {
    const frameSelector = '[data-admin-page-frame]';
    const progressSelector = '[data-admin-progress]';
    const skippedPathFragments = ['/download', '/preview', '/export', '/logout', '/announcements'];
    let controller = null;

    const shouldSkip = (link, event) => {
        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return true;
        }

        if (link.target || link.hasAttribute('download') || link.dataset.adminNoSpa !== undefined) {
            return true;
        }

        const url = new URL(link.href, window.location.href);

        if (url.origin !== window.location.origin || ! url.pathname.startsWith('/admin')) {
            return true;
        }

        return skippedPathFragments.some((fragment) => url.pathname.includes(fragment));
    };

    const setLoading = (loading) => {
        document.querySelector(frameSelector)?.classList.toggle('is-loading', loading);
        document.querySelector(progressSelector)?.classList.toggle('is-loading', loading);
    };

    const initTree = (element) => {
        window.Alpine?.initTree?.(element);
    };

    const swapSidebarNav = (nextDocument) => {
        const currentNav = document.querySelector('.sidebar-nav');
        const nextNav = nextDocument.querySelector('.sidebar-nav');

        if (! currentNav || ! nextNav) {
            return;
        }

        currentNav.innerHTML = nextNav.innerHTML;
        initTree(currentNav);
    };

    const visit = async (url, pushState = true) => {
        controller?.abort();
        controller = new AbortController();
        setLoading(true);

        try {
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-Admin-Navigation': 'partial',
                },
                signal: controller.signal,
            });

            const contentType = response.headers.get('content-type') || '';

            if (! response.ok || ! contentType.includes('text/html')) {
                window.location.href = url;
                return;
            }

            const html = await response.text();
            const nextDocument = new DOMParser().parseFromString(html, 'text/html');
            const nextFrame = nextDocument.querySelector(frameSelector);
            const currentFrame = document.querySelector(frameSelector);

            if (! nextFrame || ! currentFrame) {
                window.location.href = url;
                return;
            }

            document.title = nextDocument.title;
            swapSidebarNav(nextDocument);
            currentFrame.replaceWith(nextFrame);
            initTree(nextFrame);

            if (pushState) {
                window.history.pushState({ adminNavigation: true }, '', url);
            }

            window.scrollTo({ top: 0, behavior: 'smooth' });
            document.dispatchEvent(new CustomEvent('admin:navigated', { detail: { url } }));
        } catch (error) {
            if (error.name !== 'AbortError') {
                window.location.href = url;
            }
        } finally {
            setLoading(false);
        }
    };

    document.addEventListener('click', (event) => {
        const link = event.target.closest?.('a[href]');

        if (! link || shouldSkip(link, event)) {
            return;
        }

        event.preventDefault();
        visit(link.href);
    });

    window.addEventListener('popstate', () => visit(window.location.href, false));
})();
</script>

@stack('scripts')
</body>
</html>
