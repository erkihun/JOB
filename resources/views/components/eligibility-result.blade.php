@props([
    'result',              // App\Services\Eligibility\EligibilityResult
    'subject' => 'applicant', // 'applicant' (you) or 'staff' wording
])

@if($result->hasRules)
    @if($result->eligible)
    <div {{ $attributes->merge(['class' => 'flex items-start gap-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3']) }} role="status">
        <svg class="mt-0.5 h-5 w-5 shrink-0 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div class="text-sm">
            <p class="font-semibold text-green-800">{{ $result->message() }}</p>
            @if($result->matchedGroup)
            <p class="mt-0.5 text-green-700">
                {{ \App\Services\Eligibility\VacancyEligibilityChecker::optionLabel($result->matchedOption, $result->matchedGroup->title) }}:
                {{ $result->matchedGroup->requirements->map->summary()->implode(' + ') }}
            </p>
            @endif
        </div>
    </div>
    @else
    <div {{ $attributes->merge(['class' => 'flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3']) }} role="alert">
        <svg class="mt-0.5 h-5 w-5 shrink-0 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m0 3.75h.008M10.34 3.94L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.66 3.94a2 2 0 00-3.42 0z"/>
        </svg>
        <div class="min-w-0 text-sm">
            <p class="font-semibold text-red-800">{{ $result->message() }}</p>
            <ul class="mt-2 space-y-1.5 text-red-700">
                @foreach($result->failedOptions as $failure)
                <li>
                    <span class="font-semibold">{{ $failure['label'] }}:</span>
                    {{ implode(' ', $failure['reasons']) }}
                </li>
                @endforeach
            </ul>
            @if($subject === 'applicant')
            <p class="mt-2 text-xs text-red-700/80">{{ __('vacancies.eligibility_update_profile_hint') }}</p>
            @endif
        </div>
    </div>
    @endif
@endif
