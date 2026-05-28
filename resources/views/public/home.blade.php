@extends('layouts.public')

@section('title', __('public.home'))
@section('meta_description', 'Find the latest job vacancies and career opportunities.')

@section('content')

{{-- ══════════════════════════════════════════════════════ HERO ══ --}}
@if($sliders->isNotEmpty())
<section
    class="relative min-h-[calc(92vh-4rem)] overflow-hidden bg-slate-950 text-white"
    x-data="{ active: 0, total: {{ $sliders->count() }} }"
    x-init="total > 1 && setInterval(() => active = (active + 1) % total, 6000)"
>
    @foreach($sliders as $i => $slider)
    <div
        x-show="active === {{ $i }}"
        x-transition:enter="transition duration-1000 ease-out"
        x-transition:enter-start="opacity-0 scale-105"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition duration-1000 ease-in"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute inset-0"
        style="{{ $i === 0 ? '' : 'display:none' }}"
    >
        @if($slider->image_path)
        <div class="absolute inset-0">
            <img src="{{ Storage::disk('public')->url($slider->image_path) }}" alt="" class="h-full w-full object-cover opacity-70">
        </div>
        @endif
        <div class="absolute inset-0 bg-slate-950/70"></div>

        <div class="relative mx-auto flex min-h-[calc(92vh-4rem)] max-w-7xl items-center justify-center px-4 py-20 text-center sm:px-6 lg:px-8">
            <div class="max-w-4xl">
                @php $heroLogo = \App\Models\Setting::get('org.logo', ''); @endphp
                @if($heroLogo)
                <img src="{{ Storage::url($heroLogo) }}" alt="{{ \App\Models\Setting::get('org.name', config('app.name')) }}" class="mx-auto mb-5 h-20 w-auto sm:h-24">
                @endif

                <span class="mb-5 inline-flex items-center justify-center gap-2 rounded-full bg-orange-500 px-4 py-2 text-xs font-bold uppercase text-white">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <span>{{ \App\Models\Setting::get('org.name', config('app.name')) }}</span>
                </span>

                <h1 class="mx-auto max-w-4xl text-4xl font-black leading-tight text-white sm:text-5xl lg:text-6xl">
                    {{ $slider->getTranslation('title', app()->getLocale(), false) ?: $slider->getTranslation('title', 'en', false) }}
                </h1>

                @php $sub = $slider->getTranslation('subtitle', app()->getLocale(), false) ?: $slider->getTranslation('subtitle', 'en', false); @endphp
                @if($sub)
                <p class="mx-auto mt-5 max-w-2xl text-base leading-8 text-slate-100 sm:text-lg">{{ $sub }}</p>
                @endif

                @php
                    $btn = $slider->getTranslation('button_text', app()->getLocale(), false) ?: $slider->getTranslation('button_text', 'en', false);
                    $primaryText = $btn ?: __('public.browse_vacancies');
                    $primaryLink = $slider->button_link ?: route('vacancies.index');
                @endphp
                <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <a href="{{ $primaryLink }}"
                       class="inline-flex items-center justify-center gap-2 rounded-xl bg-orange-500 px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-orange-950/20 transition hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-300">
                        <span>{{ $primaryText }}</span>
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-6-6l6 6-6 6"/>
                        </svg>
                    </a>
                    <a href="{{ route('track.show') }}"
                       class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/25 bg-white/10 px-6 py-3.5 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white/40">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 11l3 3L22 4M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/>
                        </svg>
                        <span>{{ __('menus.track_application') }}</span>
                    </a>
                    @auth
                        @if(auth()->user()->hasRole('applicant'))
                        <a href="{{ route('applicant.dashboard') }}"
                           class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/30 bg-white/15 px-6 py-3.5 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/25 focus:outline-none focus:ring-2 focus:ring-white/40">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                            <span>{{ __('menus.dashboard') }}</span>
                        </a>
                        @endif
                    @else
                        <a href="{{ route('applicant.login') }}"
                           class="inline-flex items-center justify-center gap-2 rounded-xl bg-orange-500 px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-orange-950/20 transition hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-300">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4M10 17l5-5-5-5M15 12H3"/>
                            </svg>
                            <span>{{ __('applicant.sign_in') }}</span>
                        </a>
                    @endauth
                </div>

            </div>
        </div>
    </div>
    @endforeach

    @if($sliders->count() > 1)
    <button type="button" @click="active = (active - 1 + total) % total"
            class="absolute left-4 top-1/2 z-10 hidden h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full border border-white/20 bg-white/10 text-white backdrop-blur-sm transition hover:bg-white/20 md:flex"
            aria-label="Previous slide">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
    </button>
    <button type="button" @click="active = (active + 1) % total"
            class="absolute right-4 top-1/2 z-10 hidden h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full border border-white/20 bg-white/10 text-white backdrop-blur-sm transition hover:bg-white/20 md:flex"
            aria-label="Next slide">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </button>
    <div class="absolute bottom-7 left-0 right-0 z-10 flex justify-center gap-2">
        @foreach($sliders as $i => $slider)
        <button type="button" @click="active = {{ $i }}"
                :class="active === {{ $i }} ? 'bg-orange-500 w-8' : 'bg-white/50 w-2.5'"
                class="h-2.5 rounded-full transition-all duration-300"
                aria-label="Go to slide {{ $i + 1 }}"></button>
        @endforeach
    </div>
    @endif
</section>

@else
{{-- Default hero --}}
<section class="relative min-h-[calc(92vh-4rem)] overflow-hidden bg-slate-950 text-white">
    <div class="relative mx-auto flex min-h-[calc(92vh-4rem)] max-w-7xl items-center justify-center px-4 py-20 text-center sm:px-6 lg:px-8">
        <div class="max-w-4xl">
            @php $heroLogo = \App\Models\Setting::get('org.logo', ''); @endphp
            @if($heroLogo)
            <img src="{{ Storage::url($heroLogo) }}" alt="{{ \App\Models\Setting::get('org.name', config('app.name')) }}" class="mx-auto mb-5 h-20 w-auto sm:h-24">
            @endif

            <span class="mb-5 inline-flex items-center justify-center gap-2 rounded-full bg-orange-500 px-4 py-2 text-xs font-bold uppercase text-white">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                <span>{{ \App\Models\Setting::get('org.name', config('app.name')) }}</span>
            </span>

            <h1 class="mx-auto max-w-4xl text-4xl font-black leading-tight text-white sm:text-5xl lg:text-6xl">
                {{ __('public.hero_subtitle') }}
            </h1>

            <p class="mx-auto mt-5 max-w-2xl text-base leading-8 text-slate-100 sm:text-lg">
                {{ __('public.hero_description', ['org' => \App\Models\Setting::get('org.name', config('app.name'))]) }}
            </p>

            <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                <a href="{{ route('vacancies.index') }}"
                   class="inline-flex items-center justify-center gap-2 rounded-xl bg-orange-500 px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-orange-950/20 transition hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-300">
                    <span>{{ __('public.browse_vacancies') }}</span>
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-6-6l6 6-6 6"/>
                    </svg>
                </a>
                <a href="{{ route('track.show') }}"
                   class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/25 bg-white/10 px-6 py-3.5 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white/40">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 11l3 3L22 4M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/>
                    </svg>
                    <span>{{ __('menus.track_application') }}</span>
                </a>
                @auth
                    @if(auth()->user()->hasRole('applicant'))
                    <a href="{{ route('applicant.dashboard') }}"
                       class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/30 bg-white/15 px-6 py-3.5 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/25 focus:outline-none focus:ring-2 focus:ring-white/40">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        <span>{{ __('menus.dashboard') }}</span>
                    </a>
                    @endif
                @else
                    <a href="{{ route('applicant.login') }}"
                       class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/30 bg-white/15 px-6 py-3.5 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/25 focus:outline-none focus:ring-2 focus:ring-white/40">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4M10 17l5-5-5-5M15 12H3"/>
                        </svg>
                        <span>{{ __('applicant.sign_in') }}</span>
                    </a>
                @endauth
            </div>

        </div>
    </div>
</section>
@endif

{{-- ═══════════════════════════════════════════ ANNOUNCEMENTS ══ --}}
@if($announcements->isNotEmpty())
<section class="bg-amber-50 border-y border-amber-100 py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

        <div class="flex items-center justify-between mb-8">
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
            @foreach($announcements->take(3) as $ann)
            <article
               x-data="{ expanded: false }"
               class="group flex flex-col rounded-2xl border border-amber-100 bg-white p-5 shadow-sm hover:shadow-md hover:border-amber-200 transition-all">
                <div class="flex items-start justify-between gap-3 mb-3">
                    <h3 class="font-semibold text-gray-900 leading-snug group-hover:text-amber-700 transition text-sm sm:text-base">
                        {{ $ann->subject }}
                    </h3>
                    <span class="shrink-0 text-xs text-gray-400 mt-0.5">{{ $ann->published_at->format('d M Y') }}</span>
                </div>
                <div
                    class="flex-1 prose prose-sm max-w-none text-gray-700 text-justify"
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

        @if($announcements->count() > 3)
        <div class="mt-6 text-center">
            <a href="{{ route('announcements.index') }}"
               class="inline-block rounded-xl border border-amber-200 bg-white px-6 py-2.5 text-sm font-semibold text-amber-700 hover:bg-amber-50 transition shadow-sm">
                {{ __('public.view_all') }} ({{ $announcements->count() }})
            </a>
        </div>
        @endif
    </div>
</section>
@endif

{{-- ══════════════════════════════════════════ OPEN VACANCIES ══ --}}
<section class="py-14 sm:py-20">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

        <div class="flex items-end justify-between mb-8 sm:mb-10">
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
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($vacancies as $vacancy)
            @php
                $daysLeft = now()->diffInDays($vacancy->closing_date, false);
                $urgent = $daysLeft >= 0 && $daysLeft <= 3;
            @endphp
            <a href="{{ route('vacancies.show', $vacancy) }}"
               class="group flex flex-col rounded-2xl border border-gray-200 bg-white p-5 shadow-sm hover:shadow-lg hover:border-blue-200 hover:-translate-y-0.5 transition-all duration-200">

                <div class="flex items-start justify-between gap-3 mb-3">
                    <div class="flex-1 min-w-0">
                        @if($vacancy->code)
                        <p class="text-[10px] font-mono text-gray-400 mb-1">{{ $vacancy->code }}</p>
                        @endif
                        <h3 class="font-bold text-gray-900 group-hover:text-blue-700 transition leading-snug text-sm sm:text-base">
                            {{ $vacancy->getTranslation('title', app()->getLocale(), false) ?: $vacancy->getTranslation('title', 'en', false) }}
                        </h3>
                    </div>
                    @if($vacancy->employment_type)
                    <span class="shrink-0 rounded-lg bg-blue-50 px-2.5 py-1 text-[11px] font-semibold text-blue-700">
                        {{ $vacancy->employment_type->label() }}
                    </span>
                    @endif
                </div>

                <div class="space-y-1.5 text-xs text-gray-500 flex-1">
                    @if($vacancy->department)
                    <div class="flex items-center gap-1.5">
                        <svg class="h-3.5 w-3.5 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        <span>{{ $vacancy->department }}</span>
                    </div>
                    @endif
                    @php $loc = $vacancy->getTranslation('location', app()->getLocale(), false) ?: $vacancy->getTranslation('location', 'en', false); @endphp
                    @if($loc)
                    <div class="flex items-center gap-1.5">
                        <svg class="h-3.5 w-3.5 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>{{ $loc }}</span>
                    </div>
                    @endif
                    @if($vacancy->number_of_positions)
                    <div class="flex items-center gap-1.5">
                        <svg class="h-3.5 w-3.5 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>{{ $vacancy->number_of_positions }} {{ __('public.positions') }}</span>
                    </div>
                    @endif
                </div>

                <div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between">
                    <span class="text-xs {{ $urgent ? 'font-bold text-red-600' : 'text-gray-400' }}">
                        @if($urgent && $daysLeft >= 0)
                        <span class="inline-flex items-center gap-1">
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            {{ __('public.closes') }} {{ $vacancy->closing_date->format('M d') }}
                        </span>
                        @else
                        {{ __('public.closes') }} {{ $vacancy->closing_date->format('M d, Y') }}
                        @endif
                    </span>
                    <span class="text-xs font-semibold text-blue-600 group-hover:text-blue-800 transition flex items-center gap-0.5">
                        {{ __('vacancies.view_details') }}
                        <svg class="h-3.5 w-3.5 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </span>
                </div>
            </a>
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
        <div class="text-center mb-12">
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
            <div class="relative flex flex-col items-center text-center px-2">
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
        <div class="text-center mb-12">
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
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm hover:shadow-md transition-shadow">
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
        <div class="flex flex-col items-center text-center lg:flex-row lg:items-center lg:justify-between lg:text-left gap-8">
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
