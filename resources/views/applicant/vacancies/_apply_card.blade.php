<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white">
    <div class="border-b p-5 {{ $isPast ? 'border-red-100 bg-red-50' : ($isUrgent ? 'border-accent/20 bg-accent-muted' : 'border-brand/15 bg-brand-muted') }}">
        @if($isPast)
            <p class="font-bold text-red-700">{{ __('vacancies.deadline_passed') }}</p>
        @else
            <p class="text-[13px] font-extrabold uppercase tracking-wider {{ $isUrgent ? 'text-accent-dark' : 'text-brand-dark' }}">{{ __('public.deadline') }}</p>
            <p class="mt-1 flex items-baseline gap-2">
                @if($daysLeft === 0)
                    <span class="text-2xl font-extrabold text-accent-dark">{{ __('public.closes_today') }}</span>
                @else
                    <span class="text-4xl font-extrabold tabular-nums {{ $isUrgent ? 'text-accent-dark' : 'text-ink' }}">{{ $daysLeft }}</span>
                    <span class="font-bold text-gray-700">{{ __('public.days_left') }}</span>
                @endif
            </p>
            <p class="mt-1 text-sm text-gray-700">{{ et_date($vacancy->announcement->closing_date, 'M d, Y') }}</p>
        @endif
    </div>

    <dl class="px-5 py-2 text-[15px]">
        <div class="flex justify-between gap-3 border-b border-gray-100 py-2.5"><dt class="text-gray-600">{{ __('vacancies.opening_date') }}</dt><dd class="font-bold text-gray-900">{{ et_date($vacancy->announcement->opening_date, 'M d, Y') }}</dd></div>
        <div class="flex justify-between gap-3 border-b border-gray-100 py-2.5"><dt class="text-gray-600">{{ __('vacancies.closing_date') }}</dt><dd class="font-bold text-gray-900">{{ et_date($vacancy->announcement->closing_date, 'M d, Y') }}</dd></div>
        @if($vacancy->field_of_study)
        <div class="flex justify-between gap-3 border-b border-gray-100 py-2.5"><dt class="text-gray-600">{{ __('vacancies.field_of_study') }}</dt><dd class="text-right font-bold text-gray-900">{{ $vacancy->field_of_study }}</dd></div>
        @endif
        @if($vacancy->minimum_experience !== null)
        <div class="flex justify-between gap-3 py-2.5"><dt class="text-gray-600">{{ __('vacancies.min_experience') }}</dt><dd class="font-bold text-gray-900">{{ trans_choice('public.years_count', (int) $vacancy->minimum_experience, ['count' => $vacancy->minimum_experience]) }}</dd></div>
        @endif
    </dl>

    <div class="space-y-3 border-t border-gray-100 p-5">
        @if($alreadyApplied)
            <p class="flex items-center gap-2 rounded-xl bg-green-50 px-4 py-3 text-sm font-bold text-green-800">
                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                {{ __('vacancies.already_applied') }}
            </p>
            <a href="{{ route('applicant.applications.index') }}" class="flex h-11 items-center justify-center rounded-xl border border-gray-300 text-sm font-bold text-gray-900 hover:bg-gray-50">{{ __('applicant.my_applications') }}</a>
        @elseif($isPast)
            <p class="text-sm text-gray-600">{{ __('vacancies.deadline_passed') }}</p>
        @elseif($canApply)
            <a href="{{ route('applicant.applications.create', $vacancy) }}"
               class="flex h-12 items-center justify-center rounded-xl bg-accent-dark text-base font-extrabold text-white transition hover:bg-accent focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2">
                {{ __('vacancies.apply_now') }}
            </a>
        @else
            <p class="rounded-xl bg-gray-50 px-4 py-3 text-center text-sm text-gray-700 ring-1 ring-gray-200">{{ __('vacancies.vacancy_not_open') }}</p>
        @endif
    </div>
</div>
