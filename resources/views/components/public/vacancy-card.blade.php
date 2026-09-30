@props([
    'vacancy',
    'headingTag' => 'h3',
    'href' => null,      // override the link (e.g. the applicant portal's vacancy page)
    'applied' => false,  // show an "Applied" badge instead of the apply button
])

@php
    $locale      = app()->getLocale();
    $title       = $vacancy->getTranslation('title', $locale, false) ?: $vacancy->getTranslation('title', 'en', false);
    $loc         = $vacancy->getTranslation('location', $locale, false) ?: $vacancy->getTranslation('location', 'en', false);
    $desc        = $vacancy->getTranslation('description', $locale, false) ?: $vacancy->getTranslation('description', 'en', false);
    $descExcerpt = $desc ? Str::limit(trim(strip_tags($desc)), 170) : null;
    $closing     = $vacancy->announcement->closing_date;
    $daysLeft    = (int) today()->diffInDays($closing, false);
    $isPast      = $daysLeft < 0;
    $isUrgent    = ! $isPast && $daysLeft <= 6;
    $institution = $vacancy->institution;
    $hasMap      = $institution && $institution->latitude && $institution->longitude;
    $url         = $href ?? route('vacancies.show', $vacancy);
@endphp

{{-- Search-result card: what / where / key facts on the left, deadline + action on the right. --}}
<article {{ $attributes->merge(['class' => 'group relative flex flex-col gap-5 rounded-2xl border border-gray-200 bg-white p-5 transition hover:border-brand/50 hover:shadow-card-hover focus-within:ring-2 focus-within:ring-brand/40 sm:flex-row sm:p-6']) }}
         x-data="{ mapOpen: false }">

    <div class="flex min-w-0 flex-1 flex-col gap-2.5">
        <p class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-gray-600">
            @if($vacancy->department)<span class="font-semibold text-gray-700">{{ $vacancy->department }}</span>@endif
            @if($institution)<span class="truncate" title="{{ $institution->name }}">{{ $institution->displayName() }}</span>@endif
            @if($vacancy->code)<span class="font-mono text-[13px] text-gray-500">{{ $vacancy->code }}</span>@endif
        </p>

        <{{ $headingTag }} class="text-xl font-extrabold leading-snug text-gray-900">
            <a href="{{ $url }}" class="text-brand-dark after:absolute after:inset-0 after:rounded-2xl hover:underline focus:outline-none">{{ $title }}</a>
        </{{ $headingTag }}>

        @if($descExcerpt)
        <p class="line-clamp-2 text-[15px] leading-relaxed text-gray-600">{{ $descExcerpt }}</p>
        @endif

        <ul class="mt-1 flex flex-wrap items-center gap-2 text-[13px] font-semibold text-gray-700">
            @if($loc)
            <li class="inline-flex items-center gap-1.5 rounded-md bg-gray-100 px-2.5 py-1">
                <x-public.icon name="map-pin" class="h-3.5 w-3.5 text-gray-500" />
                {{ $loc }}
                @if($hasMap)
                <button type="button" @click="mapOpen = true"
                        class="relative z-10 ml-1 rounded font-bold text-brand hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                    {{ __('public.map') }}
                </button>
                @endif
            </li>
            @endif
            @if($vacancy->employment_type)
            <li class="rounded-md bg-gray-100 px-2.5 py-1">{{ $vacancy->employment_type->label() }}</li>
            @endif
            @if($vacancy->number_of_positions)
            <li class="rounded-md bg-gray-100 px-2.5 py-1">{{ trans_choice('public.positions_count', $vacancy->number_of_positions, ['count' => $vacancy->number_of_positions]) }}</li>
            @endif
            @if($vacancy->field_of_study)
            <li class="max-w-56 truncate rounded-md bg-gray-100 px-2.5 py-1">{{ $vacancy->field_of_study }}</li>
            @endif
        </ul>
    </div>

    <div class="flex shrink-0 items-center justify-between gap-4 border-t border-gray-100 pt-4 sm:w-48 sm:flex-col sm:items-end sm:justify-between sm:border-0 sm:pt-0">
        <div class="sm:text-right">
            @if($isPast)
                <span class="inline-flex rounded-full bg-red-50 px-3 py-1 text-sm font-bold text-red-700">{{ __('public.closed') }}</span>
            @elseif($daysLeft === 0)
                <span class="inline-flex rounded-full bg-accent-muted px-3 py-1 text-sm font-bold text-accent-dark">{{ __('public.closes_today') }}</span>
            @else
                <span class="inline-flex rounded-full px-3 py-1 text-sm font-bold {{ $isUrgent ? 'bg-accent-muted text-accent-dark' : 'bg-brand-muted text-brand-dark' }}">
                    {{ trans_choice('public.days_left_count', $daysLeft, ['count' => $daysLeft]) }}
                </span>
            @endif
            <p class="mt-1.5 text-[13px] text-gray-500">{{ __('public.closes') }} {{ et_date($closing, 'M d, Y') }}</p>
            <p class="sr-only">{{ __('vacancies.opening_date') }} {{ et_date($vacancy->announcement->opening_date, 'M d, Y') }}</p>
        </div>
        @if($applied)
        <span class="inline-flex h-11 shrink-0 items-center gap-1.5 rounded-xl bg-green-50 px-4 text-[15px] font-bold text-green-800">
            <x-public.icon name="check-circle" class="h-4 w-4" />
            {{ __('applicant.applied') }}
        </span>
        @else
        <span class="relative inline-flex h-11 shrink-0 items-center gap-1.5 rounded-xl bg-brand px-4 text-[15px] font-bold text-white transition group-hover:bg-brand-dark" aria-hidden="true">
            {{ __('public.view_and_apply') }}
            <x-public.icon name="arrow-right" class="h-4 w-4" />
        </span>
        @endif
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
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-gray-500 hover:bg-gray-100 hover:text-gray-700"
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
                       class="inline-flex items-center gap-1 text-sm font-semibold text-brand hover:underline">
                        {{ __('admin.institution_open_in_maps') }}
                        <x-public.icon name="external" class="h-3.5 w-3.5" />
                    </a>
                </div>
            </div>
        </div>
    </template>
    @endif
</article>
