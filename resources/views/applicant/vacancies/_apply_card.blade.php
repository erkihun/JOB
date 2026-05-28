<div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
    <div class="border-b border-gray-100 bg-gray-50 px-5 py-4">
        <h3 class="text-sm font-bold text-gray-900">{{ __('vacancies.apply_now') }}</h3>
    </div>
    <div class="p-5 space-y-4">

        @if($alreadyApplied)
        {{-- Already applied --}}
        <div class="flex items-center gap-2.5 rounded-xl bg-green-50 border border-green-200 px-4 py-3">
            <svg class="h-5 w-5 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <p class="text-sm font-semibold text-green-800">{{ __('vacancies.already_applied') }}</p>
        </div>
        <a href="{{ route('applicant.applications.index') }}"
           class="block w-full text-center rounded-xl border border-gray-200 px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition">
            {{ __('applicant.my_applications') }}
        </a>

        @elseif($isPast)
        {{-- Deadline passed --}}
        <div class="flex items-center gap-2.5 rounded-xl bg-red-50 border border-red-200 px-4 py-3">
            <svg class="h-5 w-5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-sm font-semibold text-red-800">{{ __('vacancies.deadline_passed') }}</p>
        </div>

        @elseif($canApply)
        {{-- Can apply --}}
        @if($isUrgent)
        <div class="flex items-center gap-2 rounded-xl bg-orange-50 border border-orange-200 px-4 py-2.5">
            <svg class="h-4 w-4 text-orange-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            <p class="text-xs font-semibold text-orange-700">
                {{ $daysLeft === 0 ? __('public.closes_today') : __('public.closes_in_days', ['days' => $daysLeft]) }}
            </p>
        </div>
        @endif
        <a href="{{ route('applicant.applications.create', $vacancy) }}"
           class="block w-full text-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-bold text-white shadow hover:bg-blue-700 active:bg-blue-800 transition focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
            {{ __('vacancies.apply_now') }}
        </a>

        @else
        {{-- Not open --}}
        <div class="rounded-xl bg-gray-50 border border-gray-200 px-4 py-3 text-sm text-gray-600 text-center">
            {{ __('vacancies.vacancy_not_open') }}
        </div>
        @endif

        {{-- Date summary --}}
        @if(!$isPast)
        <div class="rounded-xl bg-gray-50 border border-gray-100 px-4 py-3 text-xs text-gray-500 space-y-1.5">
            <div class="flex justify-between gap-2">
                <span>{{ __('vacancies.opening_date') }}</span>
                <span class="font-semibold text-gray-700">{{ et_date($vacancy->opening_date, 'M d, Y') }}</span>
            </div>
            <div class="flex justify-between gap-2">
                <span>{{ __('vacancies.closing_date') }}</span>
                <span class="font-semibold {{ $isUrgent ? 'text-red-600' : 'text-gray-700' }}">{{ et_date($vacancy->closing_date, 'M d, Y') }}</span>
            </div>
            @if($vacancy->number_of_positions)
            <div class="flex justify-between gap-2">
                <span>{{ __('vacancies.number_of_positions') }}</span>
                <span class="font-semibold text-gray-700">{{ $vacancy->number_of_positions }}</span>
            </div>
            @endif
        </div>
        @endif

    </div>
</div>
