@props(['vacancy'])

@php
    $locale   = app()->getLocale();
    $title    = $vacancy->getTranslation('title', $locale, false) ?: $vacancy->getTranslation('title', 'en', false);
    $loc      = $vacancy->getTranslation('location', $locale, false) ?: $vacancy->getTranslation('location', 'en', false);
    $closing  = $vacancy->announcement->closing_date;
    $daysLeft = (int) today()->diffInDays($closing, false);
    $isUrgent = $daysLeft >= 0 && $daysLeft <= 6;
@endphp

{{-- One scannable row of the home "Latest vacancies" table; stacks on small screens. --}}
<li class="group relative grid gap-2 border-b border-gray-100 px-5 py-4 transition last:border-0 hover:bg-gray-50 md:grid-cols-[minmax(0,3fr)_minmax(0,1.3fr)_minmax(0,1.1fr)_minmax(0,1.2fr)_1.5rem] md:items-center md:gap-4 md:px-6 md:py-5">
    <div class="min-w-0">
        <h3 class="text-[17px] font-bold leading-snug">
            <a href="{{ route('vacancies.show', $vacancy) }}" class="text-brand-dark after:absolute after:inset-0 group-hover:underline focus:outline-none">{{ $title }}</a>
        </h3>
        <p class="mt-1 truncate text-sm text-gray-600">
            {{ $vacancy->department ?: $vacancy->institution?->displayName() }}
            @if($vacancy->code)<span class="text-gray-400">·</span> <span class="font-mono text-[13px]">{{ $vacancy->code }}</span>@endif
        </p>
    </div>
    <p class="flex items-center gap-1.5 text-[15px] text-gray-700">
        <x-public.icon name="map-pin" class="h-4 w-4 text-gray-400 md:hidden" />
        {{ $loc ?: '—' }}
    </p>
    <p class="text-[15px] text-gray-700">
        <span class="font-bold text-gray-900">{{ trans_choice('public.positions_count', (int) $vacancy->number_of_positions, ['count' => (int) $vacancy->number_of_positions]) }}</span>
        @if($vacancy->employment_type)<span class="text-gray-500"> · {{ $vacancy->employment_type->label() }}</span>@endif
    </p>
    <div class="flex items-center gap-3 md:flex-col md:items-start md:gap-1">
        <span class="inline-flex rounded-full px-2.5 py-0.5 text-[13px] font-bold {{ $isUrgent ? 'bg-accent-muted text-accent-dark' : 'bg-brand-muted text-brand-dark' }}">
            {{ $daysLeft === 0 ? __('public.closes_today') : trans_choice('public.days_left_count', $daysLeft, ['count' => $daysLeft]) }}
        </span>
        <span class="text-[13px] text-gray-500">{{ et_date($closing, 'M d, Y') }}</span>
    </div>
    <x-public.icon name="chevron-right" class="hidden h-5 w-5 text-brand transition group-hover:translate-x-0.5 md:block" />
</li>
