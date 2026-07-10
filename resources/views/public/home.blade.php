@extends('layouts.public')

@section('title', __('public.home'))
@section('meta_description', 'Find the latest job vacancies and career opportunities.')

@section('content')

{{-- ══════════════════════════════════════════════════════ HERO ══ --}}
@php $orgName = \App\Models\Setting::get('org.name', config('app.name')); @endphp

@if($sliders->isNotEmpty())
{{-- ── Slider hero ── --}}
<section
    class="relative h-screen min-h-[600px] max-h-[820px] overflow-hidden bg-slate-950"
    x-data="{ active: 0, total: {{ $sliders->count() }} }"
    x-init="total > 1 && setInterval(() => active = (active + 1) % total, 6000)"
>
    {{-- Slides --}}
    @foreach($sliders as $i => $slider)
    <div
        x-show="active === {{ $i }}"
        x-transition:enter="transition duration-[1200ms] ease-out"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition duration-[600ms] ease-in"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="absolute inset-0"
        style="{{ $i === 0 ? '' : 'display:none' }}"
    >
        {{-- BG --}}
        @if($slider->image_path)
        <img src="{{ Storage::disk('public')->url($slider->image_path) }}" alt=""
             class="absolute inset-0 h-full w-full object-cover scale-[1.03] transition-transform duration-[8000ms]"
             style="animation: kenBurns 8s ease-out forwards">
        @else
        <div class="absolute inset-0"
             style="background: linear-gradient(135deg,#0f172a 0%,#1e3a8a 50%,#0f172a 100%)">
            <div class="absolute inset-0" style="background:radial-gradient(ellipse at 30% 50%,rgba(59,130,246,.25) 0%,transparent 60%),radial-gradient(ellipse at 75% 30%,rgba(99,102,241,.2) 0%,transparent 55%)"></div>
        </div>
        @endif
        {{-- Overlay: dark at bottom for text, subtle at top --}}
        <div class="absolute inset-0" style="background:linear-gradient(to top, rgba(0,0,0,.85) 0%, rgba(0,0,0,.35) 45%, rgba(0,0,0,.05) 100%)"></div>
    </div>
    @endforeach

    {{-- Single centred content block --}}
    <div class="relative z-10 flex h-full flex-col items-center justify-end pb-16 px-4 text-center sm:pb-20">

        {{-- Org pill --}}
        <p class="mb-4 text-[11px] font-bold uppercase tracking-[0.25em] text-white/60">
            {{ $orgName }}
        </p>

        {{-- Slide title — changes with slide --}}
        @foreach($sliders as $i => $slider)
        <h1
            x-show="active === {{ $i }}"
            x-transition:enter="transition duration-700 delay-200"
            x-transition:enter-start="opacity-0 translate-y-4"
            x-transition:enter-end="opacity-100 translate-y-0"
            class="max-w-2xl text-2xl font-black leading-tight text-white sm:text-4xl lg:text-5xl"
            style="{{ $i === 0 ? '' : 'display:none' }}"
        >
            {{ $slider->getTranslation('title', app()->getLocale(), false) ?: $slider->getTranslation('title', 'en', false) }}
        </h1>
        @endforeach

        {{-- Search bar — always visible, white card --}}
        <div class="mt-8 w-full max-w-2xl">
            <x-public.vacancy-search />
        </div>

        {{-- Minimal ghost links --}}
        <div class="mt-5 flex items-center gap-4">
            <a href="{{ route('vacancies.index') }}"
               class="text-xs font-semibold text-white/70 hover:text-white transition underline-offset-4 hover:underline">
                {{ __('public.browse_vacancies') }}
            </a>
            <span class="text-white/30">·</span>
            <a href="{{ route('track.show') }}"
               class="text-xs font-semibold text-white/70 hover:text-white transition underline-offset-4 hover:underline">
                {{ __('menus.track_application') }}
            </a>
            @guest
            <span class="text-white/30">·</span>
            <a href="{{ route('applicant.register') }}"
               class="text-xs font-semibold text-orange-400 hover:text-orange-300 transition underline-offset-4 hover:underline">
                {{ __('menus.register') }}
            </a>
            @endguest
        </div>
    </div>

    {{-- Slide counter top-right --}}
    @if($sliders->count() > 1)
    <div class="absolute top-6 right-6 z-20 flex items-center gap-3">
        <span class="text-xs font-semibold text-white/50 tabular-nums">
            <span x-text="active + 1">1</span><span class="text-white/30">/{{ $sliders->count() }}</span>
        </span>
        <div class="flex gap-1">
            @foreach($sliders as $i => $slider)
            <button type="button" @click="active = {{ $i }}"
                    :class="active === {{ $i }} ? 'bg-white w-5' : 'bg-white/35 w-1.5'"
                    class="h-1.5 rounded-full transition-all duration-500"
                    aria-label="Slide {{ $i + 1 }}"></button>
            @endforeach
        </div>
    </div>

    {{-- Prev / Next --}}
    <button type="button" @click="active = (active - 1 + total) % total"
            class="absolute left-4 top-1/2 z-20 hidden h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white backdrop-blur-sm transition hover:bg-white/25 md:flex"
            aria-label="Previous">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
    </button>
    <button type="button" @click="active = (active + 1) % total"
            class="absolute right-4 top-1/2 z-20 hidden h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white backdrop-blur-sm transition hover:bg-white/25 md:flex"
            aria-label="Next">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </button>
    @endif

    {{-- Scroll hint --}}
    <div class="absolute bottom-5 left-1/2 z-20 -translate-x-1/2 animate-bounce">
        <svg class="h-5 w-5 text-white/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </div>
</section>

{{-- Ken Burns keyframe --}}
<style>
@keyframes kenBurns { from { transform: scale(1.05); } to { transform: scale(1); } }
</style>
<div class="h-6 bg-white"></div>

@else
{{-- ── Default hero (no slider images) ── --}}
<section class="relative overflow-hidden bg-gradient-to-br from-blue-950 via-indigo-950 to-slate-950 text-white">
    {{-- Subtle radial glows --}}
    <div class="pointer-events-none absolute inset-0">
        <div class="absolute -top-32 -left-32 h-[500px] w-[500px] rounded-full bg-blue-600/20 blur-[120px]"></div>
        <div class="absolute -bottom-32 -right-32 h-[400px] w-[400px] rounded-full bg-indigo-600/20 blur-[100px]"></div>
    </div>

    <div class="relative mx-auto flex max-w-4xl flex-col items-center justify-center px-5 py-24 text-center sm:px-8 sm:py-32">

        {{-- Eyebrow --}}
        <span class="mb-5 inline-flex items-center gap-2 rounded-full border border-orange-400/30 bg-orange-500/10 px-4 py-1.5 text-xs font-bold uppercase tracking-widest text-orange-300">
            <span class="h-1.5 w-1.5 rounded-full bg-orange-400 animate-pulse"></span>
            {{ $orgName }}
        </span>

        {{-- Headline --}}
        <h1 class="text-2xl font-black leading-[1.2] tracking-tight text-white sm:text-3xl lg:text-4xl">
            {{ __('public.hero_subtitle') }}
        </h1>

        {{-- Sub --}}
        <p class="mx-auto mt-5 max-w-xl text-base leading-7 text-slate-300 sm:text-lg">
            {{ __('public.hero_description', ['org' => $orgName]) }}
        </p>

        {{-- CTAs --}}
        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
            <a href="{{ route('vacancies.index') }}"
               class="inline-flex items-center gap-1.5 rounded-xl bg-orange-500 px-5 py-2.5 text-sm font-bold text-white shadow-lg transition hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-300">
                {{ __('public.browse_vacancies') }}
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-6-6l6 6-6 6"/>
                </svg>
            </a>
            <a href="{{ route('track.show') }}"
               class="inline-flex items-center gap-1.5 rounded-xl border border-white/25 bg-white/10 px-5 py-2.5 text-sm font-semibold text-white/90 backdrop-blur-sm transition hover:bg-white/20">
                {{ __('menus.track_application') }}
            </a>
            @guest
            <a href="{{ route('applicant.register') }}"
               class="inline-flex items-center gap-1.5 rounded-xl border border-white/25 bg-white/10 px-5 py-2.5 text-sm font-semibold text-white/90 backdrop-blur-sm transition hover:bg-white/20">
                {{ __('menus.register') }}
            </a>
            @endguest
        </div>

    </div>

    {{-- Bottom fade to white --}}
    <div class="absolute bottom-0 left-0 right-0 h-20 bg-gradient-to-t from-white to-transparent"></div>
</section>

{{-- Search bar sits right below the hero, overlapping the fade --}}
<div class="relative z-10 -mt-8 mx-auto w-full max-w-3xl px-4">
    <x-public.vacancy-search />
</div>
<div class="h-8"></div>
@endif

{{-- ═══════════════════════════════════════════ ANNOUNCEMENTS ══ --}}
@if($announcements->isNotEmpty())
<section class="bg-amber-50 border-y border-amber-100 py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

        <div class="flex items-center justify-between mb-8 scroll-animate sa-fade">
            <div class="flex items-center gap-3">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-100">
                    <svg class="h-5 w-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                    </svg>
                </span>
                <h2 class="text-xl font-bold text-gray-900">{{ __('menus.announcements') }}</h2>
            </div>
            <a href="{{ route('announcements.index') }}"
               class="flex items-center gap-1 text-sm font-semibold text-amber-700 hover:text-amber-900 transition">
                {{ __('public.view_all') }}
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>

        <div class="grid gap-4">
            @foreach($announcements as $ann)
            <article
               x-data="{ expanded: false }"
               class="group flex flex-col rounded-2xl border border-amber-100 bg-white p-5 shadow-sm hover:shadow-md hover:border-amber-200 transition-all scroll-animate"
               data-delay="{{ $loop->iteration }}">
                <div class="flex items-start justify-between gap-3 mb-3">
                    <h3 class="font-semibold text-gray-900 leading-snug group-hover:text-amber-700 transition text-sm sm:text-base">
                        {{ $ann->subject }}
                    </h3>
                    <span class="shrink-0 text-xs text-gray-400 mt-0.5">{{ et_date($ann->published_at, 'd M Y') }}</span>
                </div>
                <div
                    class="flex-1 prose prose-sm max-w-none text-gray-700 text-justify announcement-content"
                    :class="expanded ? '' : 'line-clamp-3'"
                >
                    {!! $ann->renderableHtml() !!}
                </div>
                <button
                    type="button"
                    @click="expanded = !expanded"
                    class="mt-4 self-start flex items-center gap-1 text-xs font-semibold text-amber-600 hover:text-amber-800 transition"
                >
                    <span x-show="!expanded">{{ __('public.read_more') }}</span>
                    <span x-show="expanded" x-cloak>{{ __('public.show_less') }}</span>
                    <svg class="h-3.5 w-3.5 transition-transform" :class="expanded ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
            </article>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ══════════════════════════════════════════ OPEN VACANCIES ══ --}}
<section class="py-14 sm:py-20">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

        <div class="flex items-end justify-between mb-8 sm:mb-10 scroll-animate sa-fade">
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-blue-600 mb-1">{{ __('public.now_hiring') }}</p>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-gray-900">{{ __('public.open_vacancies') }}</h2>
            </div>
            @if($vacancies->isNotEmpty())
            <a href="{{ route('vacancies.index') }}"
               class="flex items-center gap-1 text-sm font-semibold text-blue-600 hover:text-blue-800 transition whitespace-nowrap">
                {{ __('public.view_all') }}
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
            @endif
        </div>

        @if($vacancies->isEmpty())
        <div class="rounded-2xl border border-dashed border-gray-200 bg-white p-16 text-center">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 mb-4">
                <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
            <p class="text-gray-500 font-medium">{{ __('public.no_vacancies') }}</p>
        </div>
        @else
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($vacancies as $vacancy)
            @php
                $daysLeft    = (int) now()->diffInDays($vacancy->closing_date, false);
                $urgent      = $daysLeft >= 0 && $daysLeft <= 3;
                $isUrgent    = $daysLeft >= 0 && $daysLeft <= 6;
                $isPast      = $daysLeft < 0;
                $hasMap      = $vacancy->institution && $vacancy->institution->latitude && $vacancy->institution->longitude;
                $mapId       = 'map-modal-home-' . $vacancy->id;
                $loc         = $vacancy->getTranslation('location', app()->getLocale(), false) ?: $vacancy->getTranslation('location', 'en', false);
                $desc        = $vacancy->getTranslation('description', app()->getLocale(), false) ?: $vacancy->getTranslation('description', 'en', false);
                $descExcerpt = $desc ? Str::limit(strip_tags($desc), 100) : null;
            @endphp

            {{-- Map modal --}}
            @if($hasMap)
            <div id="{{ $mapId }}"
                 x-data="{ open: false }"
                 x-show="open"
                 x-cloak
                 @keydown.escape.window="open = false"
                 class="fixed inset-0 z-50 flex items-center justify-center p-4"
                 style="display:none">
                {{-- Backdrop --}}
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="open = false"></div>
                {{-- Modal --}}
                <div class="relative z-10 w-full max-w-lg rounded-2xl bg-white shadow-2xl overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                        <div class="min-w-0">
                            <p class="font-semibold text-gray-900 text-sm truncate">{{ $vacancy->institution->name }}</p>
                            @if($vacancy->institution->address)
                            <p class="text-xs text-gray-400 truncate">{{ $vacancy->institution->address }}</p>
                            @endif
                        </div>
                        <button type="button" @click="open = false"
                                class="ml-3 shrink-0 flex h-8 w-8 items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <iframe
                        width="100%" height="300" style="border:0;display:block;" loading="lazy"
                        allowfullscreen referrerpolicy="no-referrer-when-downgrade"
                        src="https://www.google.com/maps?q={{ $vacancy->institution->latitude }},{{ $vacancy->institution->longitude }}&hl={{ app()->getLocale() }}&z=15&output=embed">
                    </iframe>
                    <div class="px-4 py-2.5 border-t border-gray-100 flex justify-end">
                        <a href="https://www.google.com/maps?q={{ $vacancy->institution->latitude }},{{ $vacancy->institution->longitude }}"
                           target="_blank" rel="noopener noreferrer"
                           class="text-xs font-medium text-blue-600 hover:underline">
                            {{ __('admin.institution_open_in_maps') }} ↗
                        </a>
                    </div>
                </div>
            </div>
            @endif

            <div x-data="{}" class="group relative flex flex-col rounded-2xl border border-gray-200 bg-white shadow-sm hover:shadow-lg hover:border-blue-200 hover:-translate-y-0.5 transition-all duration-200 overflow-hidden scroll-animate" data-delay="{{ ($loop->index % 3) + 1 }}">

                {{-- Top colour bar — clipped by overflow-hidden to follow card corners --}}
                @if($isUrgent && !$isPast)
                <div class="absolute top-0 inset-x-0 h-1 bg-linear-to-r from-orange-400 to-transparent"></div>
                @elseif(!$isPast)
                <div class="absolute top-0 inset-x-0 h-1 bg-linear-to-r from-green-400 to-transparent"></div>
                @endif

                <div class="flex flex-col flex-1 p-5 pt-6">

                    {{-- Header --}}
                    <div class="flex items-start justify-between gap-3 mb-3">
                        <div class="flex-1 min-w-0">
                            @if($vacancy->code)
                            <span class="inline-block font-mono text-[10px] font-medium text-gray-400 bg-gray-100 rounded px-1.5 py-0.5 mb-1.5">{{ $vacancy->code }}</span>
                            @endif
                            <h3 class="font-bold text-gray-900 group-hover:text-blue-700 transition leading-snug text-sm sm:text-base">
                                {{ $vacancy->getTranslation('title', app()->getLocale(), false) ?: $vacancy->getTranslation('title', 'en', false) }}
                            </h3>
                        </div>
                        @if($vacancy->employment_type)
                        <span class="shrink-0 rounded-lg bg-blue-50 border border-blue-100 px-2.5 py-1 text-[11px] font-semibold text-blue-700 whitespace-nowrap">
                            {{ $vacancy->employment_type->label() }}
                        </span>
                        @endif
                    </div>

                    {{-- Meta chips --}}
                    <div class="flex flex-wrap gap-2 text-xs text-gray-500 mb-3">
                        @if($vacancy->institution)
                        <span class="inline-flex items-center gap-1 rounded-lg bg-indigo-50 border border-indigo-100 px-2 py-1 text-indigo-700 font-medium">
                            <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>
                            </svg>
                            {{ Str::limit($vacancy->institution->displayName(), 30) }}
                            @if($hasMap)
                            <button type="button"
                                    @click.prevent.stop="document.getElementById('{{ $mapId }}')._x_dataStack[0].open = true"
                                    title="{{ __('admin.institution_open_in_maps') }}"
                                    class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-600 hover:bg-blue-200 transition">
                                <svg class="h-2.5 w-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </button>
                            @endif
                        </span>
                        @endif
                        @if($vacancy->department)
                        <span class="inline-flex items-center gap-1 rounded-lg bg-gray-50 border border-gray-100 px-2 py-1">
                            <svg class="h-3.5 w-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            {{ Str::limit($vacancy->department, 25) }}
                        </span>
                        @endif
                        @if($loc)
                        <span class="inline-flex items-center gap-1 rounded-lg bg-gray-50 border border-gray-100 px-2 py-1">
                            <svg class="h-3.5 w-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            {{ $loc }}
                        </span>
                        @endif
                        @if($vacancy->number_of_positions)
                        <span class="inline-flex items-center gap-1 rounded-lg bg-gray-50 border border-gray-100 px-2 py-1">
                            <svg class="h-3.5 w-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            {{ $vacancy->number_of_positions }} {{ __('public.positions') }}
                        </span>
                        @endif
                    </div>

                    {{-- Description excerpt --}}
                    @if($descExcerpt)
                    <p class="text-xs text-gray-500 leading-relaxed mb-4 flex-1">{{ $descExcerpt }}</p>
                    @else
                    <div class="flex-1"></div>
                    @endif

                    {{-- Footer --}}
                    <div class="mt-auto pt-4 border-t border-gray-100 flex items-center justify-between gap-2">
                        {{-- Deadline status --}}
                        @if($isPast)
                        <span class="text-xs font-semibold text-red-600 inline-flex items-center gap-1">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            {{ __('vacancies.deadline_passed') }}
                        </span>
                        @elseif($isUrgent)
                        <span class="text-xs font-bold text-orange-600 inline-flex items-center gap-1">
                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            {{ $daysLeft === 0 ? __('public.closes_today') : __('public.closes_in_days', ['days' => $daysLeft]) }}
                        </span>
                        @else
                        <span class="text-xs text-gray-400">
                            {{ __('public.closes') }} {{ et_date($vacancy->closing_date, 'M d, Y') }}
                        </span>
                        @endif

                        {{-- CTA --}}
                        <a href="{{ route('vacancies.show', $vacancy) }}"
                           class="inline-flex items-center gap-1 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700 transition shrink-0">
                            {{ __('vacancies.apply_now') }}
                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-6-6l6 6-6 6"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="mt-8 text-center">
            <a href="{{ route('vacancies.index') }}"
               class="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-6 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50 hover:border-blue-300 transition shadow-sm">
                {{ __('public.view_all_vacancies') }}
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
        @endif
    </div>
</section>

{{-- ══════════════════════════════════════════ HOW TO APPLY ══ --}}
<section class="bg-white border-t border-gray-100 py-14 sm:py-20">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12 scroll-animate sa-fade">
            <p class="text-xs font-semibold uppercase tracking-widest text-blue-600 mb-2">{{ __('public.simple_steps') }}</p>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-gray-900">{{ __('public.how_it_works') }}</h2>
        </div>

        <div class="relative grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
            {{-- Connecting line (desktop only) --}}
            <div class="absolute top-6 left-[12.5%] right-[12.5%] h-px bg-gradient-to-r from-blue-200 via-indigo-200 to-purple-200 hidden lg:block"></div>

            @foreach([
                ['num' => '1', 'title' => __('public.process_step_1_title'), 'desc' => __('public.process_step_1_desc'), 'bg' => 'bg-blue-600', 'ring' => 'ring-blue-200'],
                ['num' => '2', 'title' => __('public.process_step_2_title'), 'desc' => __('public.process_step_2_desc'), 'bg' => 'bg-indigo-600', 'ring' => 'ring-indigo-200'],
                ['num' => '3', 'title' => __('public.process_step_3_title'), 'desc' => __('public.process_step_3_desc'), 'bg' => 'bg-violet-600', 'ring' => 'ring-violet-200'],
                ['num' => '4', 'title' => __('public.process_step_4_title'), 'desc' => __('public.process_step_4_desc'), 'bg' => 'bg-purple-600', 'ring' => 'ring-purple-200'],
            ] as $step)
            <div class="relative flex flex-col items-center text-center px-2 scroll-animate sa-scale" data-delay="{{ $loop->iteration }}">
                <div class="relative mb-5">
                    <div class="h-12 w-12 rounded-full {{ $step['bg'] }} ring-4 {{ $step['ring'] }} text-white text-lg font-extrabold flex items-center justify-center shadow-md">
                        {{ $step['num'] }}
                    </div>
                </div>
                <h3 class="font-bold text-gray-900 mb-2">{{ $step['title'] }}</h3>
                <p class="text-sm text-gray-500 leading-relaxed">{{ $step['desc'] }}</p>
            </div>
            @endforeach
        </div>

        <div class="mt-12 text-center">
            <a href="{{ route('applicant.register') }}"
               class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-7 py-3.5 text-sm font-bold text-white shadow-lg hover:bg-blue-700 transition-all hover:scale-105">
                {{ __('public.get_started') }}
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
            </a>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════ WHY APPLY WITH US ══ --}}
<section class="bg-gray-50 border-t border-gray-100 py-14 sm:py-20">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12 scroll-animate sa-fade">
            <p class="text-xs font-semibold uppercase tracking-widest text-blue-600 mb-2">{{ __('public.our_commitment') }}</p>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-gray-900">{{ __('public.why_apply_title') }}</h2>
        </div>

        <div class="grid gap-6 sm:grid-cols-3">
            @foreach([
                [
                    'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                    'title' => __('public.why_apply_1_title'),
                    'desc' => __('public.why_apply_1_desc'),
                    'color' => 'text-green-600',
                    'bg' => 'bg-green-50',
                    'ring' => 'ring-green-100',
                ],
                [
                    'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
                    'title' => __('public.why_apply_2_title'),
                    'desc' => __('public.why_apply_2_desc'),
                    'color' => 'text-blue-600',
                    'bg' => 'bg-blue-50',
                    'ring' => 'ring-blue-100',
                ],
                [
                    'icon' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6',
                    'title' => __('public.why_apply_3_title'),
                    'desc' => __('public.why_apply_3_desc'),
                    'color' => 'text-violet-600',
                    'bg' => 'bg-violet-50',
                    'ring' => 'ring-violet-100',
                ],
            ] as $item)
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm hover:shadow-md transition-shadow scroll-animate" data-delay="{{ $loop->iteration }}">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl {{ $item['bg'] }} ring-2 {{ $item['ring'] }} mb-5">
                    <svg class="h-6 w-6 {{ $item['color'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/>
                    </svg>
                </div>
                <h3 class="font-bold text-gray-900 mb-2">{{ $item['title'] }}</h3>
                <p class="text-sm text-gray-500 leading-relaxed">{{ $item['desc'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════════════════════════ TRACK APPLICATION CTA ══ --}}
<section class="relative overflow-hidden bg-gradient-to-r from-blue-600 to-indigo-600 py-14 sm:py-20 text-white">
    <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,_rgba(255,255,255,0.1),_transparent_60%)]"></div>
    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col items-center text-center lg:flex-row lg:items-center lg:justify-between lg:text-left gap-8 scroll-animate sa-fade">
            <div class="max-w-xl">
                <h2 class="text-2xl sm:text-3xl font-extrabold">{{ __('public.track_cta_title') }}</h2>
                <p class="mt-3 text-blue-100 leading-relaxed">{{ __('public.track_cta_desc') }}</p>
            </div>
            <div class="flex flex-col sm:flex-row items-center gap-3 shrink-0">
                <a href="{{ route('track.show') }}"
                   class="rounded-xl bg-white px-7 py-3.5 text-sm font-bold text-blue-700 shadow-lg hover:bg-blue-50 transition-all hover:scale-105">
                    {{ __('public.track_cta_button') }}
                </a>
                @guest
                <a href="{{ route('applicant.register') }}"
                   class="rounded-xl border border-white/30 bg-white/10 px-7 py-3.5 text-sm font-semibold text-white hover:bg-white/20 transition-all">
                    {{ __('menus.register') }}
                </a>
                @endguest
            </div>
        </div>
    </div>
</section>

@endsection
