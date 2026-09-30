@props([
    'vacancy',
    'headingTag' => 'h3',
])

@php
    $locale     = app()->getLocale();
    $title       = $vacancy->getTranslation('title', $locale, false) ?: $vacancy->getTranslation('title', 'en', false);
    $loc         = $vacancy->getTranslation('location', $locale, false) ?: $vacancy->getTranslation('location', 'en', false);
    $desc        = $vacancy->getTranslation('description', $locale, false) ?: $vacancy->getTranslation('description', 'en', false);
    $descExcerpt = $desc ? Str::limit(trim(strip_tags($desc)), 140) : null;
    $daysLeft    = (int) today()->diffInDays($vacancy->announcement->closing_date, false);
    $isPast      = $daysLeft < 0;
    $isUrgent    = ! $isPast && $daysLeft <= 6;
    $institution = $vacancy->institution;
    $hasMap      = $institution && $institution->latitude && $institution->longitude;
@endphp

<article {{ $attributes->merge(['class' => 'group relative flex flex-col rounded-2xl border border-gray-200 bg-white p-5 shadow-card transition hover:border-brand/40 hover:shadow-card-hover focus-within:ring-2 focus-within:ring-brand/40']) }}
         x-data="{ mapOpen: false }">

    {{-- Header: institution mark + employment type --}}
    <div class="flex items-start justify-between gap-3">
        <div class="flex min-w-0 items-center gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-muted text-brand">
                <x-public.icon name="building" class="h-5 w-5" />
            </span>
            <div class="min-w-0">
                @if($institution)
                <p class="truncate text-xs font-semibold text-gray-700" title="{{ $institution->name }}">{{ $institution->displayName() }}</p>
                @endif
                @if($vacancy->code)
                <p class="font-mono text-[11px] text-gray-400">{{ $vacancy->code }}</p>
                @endif
            </div>
        </div>
        @if($vacancy->employment_type)
        <span class="shrink-0 whitespace-nowrap rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-700">
            {{ $vacancy->employment_type->label() }}
        </span>
        @endif
    </div>

    {{-- Title (stretched link makes the whole card clickable) --}}
    <{{ $headingTag }} class="mt-4 text-base font-bold leading-snug text-gray-900 group-hover:text-brand">
        <a href="{{ route('vacancies.show', $vacancy) }}" class="after:absolute after:inset-0 after:rounded-2xl focus:outline-none">
            {{ $title }}
        </a>
    </{{ $headingTag }}>

    {{-- Meta --}}
    <ul class="mt-3 flex flex-wrap gap-x-4 gap-y-1.5 text-xs text-gray-500">
        @if($vacancy->department)
        <li class="inline-flex items-center gap-1.5">
            <x-public.icon name="office" class="h-3.5 w-3.5 text-gray-400" />
            {{ Str::limit($vacancy->department, 28) }}
        </li>
        @endif
        @if($loc)
        <li class="inline-flex items-center gap-1.5">
            <x-public.icon name="map-pin" class="h-3.5 w-3.5 text-gray-400" />
            {{ $loc }}
            @if($hasMap)
            <button type="button" @click="mapOpen = true"
                    class="relative z-10 rounded font-semibold text-brand hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                {{ __('public.map') }}
            </button>
            @endif
        </li>
        @endif
        @if($vacancy->number_of_positions)
        <li class="inline-flex items-center gap-1.5">
            <x-public.icon name="users" class="h-3.5 w-3.5 text-gray-400" />
            {{ $vacancy->number_of_positions }} {{ __('public.positions') }}
        </li>
        @endif
    </ul>

    @if($descExcerpt)
    <p class="mt-3 line-clamp-2 text-sm leading-relaxed text-gray-500">{{ $descExcerpt }}</p>
    @endif

    <dl class="mt-4 space-y-1 text-xs text-gray-500">
        <div class="flex flex-wrap justify-between gap-2"><dt>{{ __('vacancies.opening_date') }}</dt><dd class="font-semibold text-gray-700">{{ et_date($vacancy->announcement->opening_date, 'M d, Y') }}</dd></div>
        <div class="flex flex-wrap justify-between gap-2"><dt>{{ __('vacancies.closing_date') }}</dt><dd class="font-semibold text-gray-700">{{ et_date($vacancy->announcement->closing_date, 'M d, Y') }}</dd></div>
    </dl>
    {{-- Footer --}}
    <div class="mt-auto pt-5"></div>
    <div class="flex items-center justify-between gap-3 border-t border-gray-100 pt-4">
        @if($isPast)
            <span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700">
                <x-public.icon name="x-circle" class="h-3.5 w-3.5" />
                {{ __('public.closed') }}
            </span>
        @elseif($isUrgent)
            <span class="inline-flex items-center gap-1.5 rounded-full bg-orange-50 px-2.5 py-1 text-xs font-semibold text-orange-700">
                <x-public.icon name="clock" class="h-3.5 w-3.5" />
                {{ $daysLeft === 0 ? __('public.closes_today') : __('public.closes_in_days', ['days' => $daysLeft]) }}
            </span>
        @else
            <span class="inline-flex items-center gap-1.5 text-xs text-gray-500">
                <x-public.icon name="calendar" class="h-3.5 w-3.5 text-gray-400" />
                {{ __('public.closes') }} <span class="font-semibold text-gray-700">{{ et_date($vacancy->announcement->closing_date, 'M d, Y') }}</span>
            </span>
        @endif

        <span class="inline-flex shrink-0 items-center gap-1 text-xs font-semibold text-brand">
            {{ __('vacancies.view_details') }}
            <x-public.icon name="arrow-right" class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5" />
        </span>
    </div>

    {{-- Map modal (teleported so card positioning never clips it) --}}
    @if($hasMap)
    <template x-teleport="body">
        <div x-show="mapOpen" x-cloak
             @keydown.escape.window="mapOpen = false"
             class="fixed inset-0 z-60 flex items-center justify-center p-4"
             role="dialog" aria-modal="true" aria-label="{{ $institution->name }}">
            <div class="absolute inset-0 bg-gray-950/60 backdrop-blur-sm" @click="mapOpen = false"></div>
            <div class="relative w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-4 py-3">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-gray-900">{{ $institution->name }}</p>
                        @if($institution->address)
                        <p class="truncate text-xs text-gray-500">{{ $institution->address }}</p>
                        @endif
                    </div>
                    <button type="button" @click="mapOpen = false"
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                            aria-label="{{ __('public.close') }}">
                        <x-public.icon name="x" />
                    </button>
                </div>
                <template x-if="mapOpen">
                    <iframe width="100%" height="300" style="border:0;display:block;" loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade" title="{{ $institution->name }}"
                            src="https://www.google.com/maps?q={{ $institution->latitude }},{{ $institution->longitude }}&hl={{ $locale }}&z=15&output=embed"></iframe>
                </template>
                <div class="flex justify-end border-t border-gray-100 px-4 py-2.5">
                    <a href="https://www.google.com/maps?q={{ $institution->latitude }},{{ $institution->longitude }}"
                       target="_blank" rel="noopener noreferrer"
                       class="inline-flex items-center gap-1 text-xs font-semibold text-brand hover:underline">
                        {{ __('admin.institution_open_in_maps') }}
                        <x-public.icon name="external" class="h-3.5 w-3.5" />
                    </a>
                </div>
            </div>
        </div>
    </template>
    @endif
</article>
