@extends('layouts.public')

@section('title', __('applications.track_title'))

@section('content')
@php
    $inputClass = 'h-11 w-full rounded-xl border bg-white pl-10 pr-3 text-sm text-gray-900 shadow-sm transition placeholder:text-gray-400 focus:outline-none focus:ring-2';
@endphp

<x-public.page-header :title="__('applications.track_title')"
                      :subtitle="__('applications.track_subtitle')"
                      :crumbs="[['label' => __('menus.track_application')]]"
                      width="max-w-3xl" />

<div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">

    {{-- Lookup form --}}
    <form method="POST" action="{{ route('track.search') }}"
          class="relative z-10 -mt-6 rounded-2xl border border-gray-200 bg-white p-5 shadow-lg shadow-gray-900/5 sm:p-6"
          x-data="{ busy: false }" @submit="busy = true">
        @csrf
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="reference_number" class="mb-1.5 block text-xs font-semibold text-gray-600">
                    {{ __('applications.reference_number') }}
                </label>
                <div class="relative">
                    <x-public.icon name="hashtag" class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400" />
                    <input type="text" id="reference_number" name="reference_number" required
                           value="{{ old('reference_number', isset($application) ? $application->reference_number : '') }}"
                           placeholder="APP-2024-000001" autocomplete="off" spellcheck="false"
                           @error('reference_number') aria-invalid="true" aria-describedby="reference_number_error" @enderror
                           class="{{ $inputClass }} font-mono uppercase placeholder:normal-case @error('reference_number') border-red-300 focus:border-red-500 focus:ring-red-500/20 @else border-gray-200 focus:border-brand focus:ring-brand/20 @enderror">
                </div>
                @error('reference_number')
                    <p id="reference_number_error" class="mt-1.5 flex items-center gap-1 text-xs text-red-600">
                        <x-public.icon name="alert" class="h-3.5 w-3.5" /> {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <label for="identifier" class="mb-1.5 block text-xs font-semibold text-gray-600">
                    {{ __('applications.track_identifier_label') }}
                </label>
                <div class="relative">
                    <x-public.icon name="mail" class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400" />
                    <input type="text" id="identifier" name="identifier" required
                           value="{{ old('identifier') }}"
                           placeholder="{{ __('applications.track_identifier_placeholder') }}" autocomplete="email"
                           @error('identifier') aria-invalid="true" aria-describedby="identifier_error" @enderror
                           class="{{ $inputClass }} @error('identifier') border-red-300 focus:border-red-500 focus:ring-red-500/20 @else border-gray-200 focus:border-brand focus:ring-brand/20 @enderror">
                </div>
                @error('identifier')
                    <p id="identifier_error" class="mt-1.5 flex items-center gap-1 text-xs text-red-600">
                        <x-public.icon name="alert" class="h-3.5 w-3.5" /> {{ $message }}
                    </p>
                @enderror
            </div>
        </div>

        <div class="mt-5 flex flex-col-reverse items-stretch gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="flex items-center gap-1.5 text-xs text-gray-500">
                <x-public.icon name="lock" class="h-3.5 w-3.5 text-gray-400" />
                {{ __('public.track_privacy_note') }}
            </p>
            <button type="submit" :disabled="busy"
                    class="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-xl bg-brand px-6 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-dark disabled:opacity-70 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                <svg x-show="busy" x-cloak class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <x-public.icon name="search" x-show="!busy" />
                {{ __('applications.track_submit') }}
            </button>
        </div>
    </form>

    {{-- Result --}}
    @isset($application)
    @php
        $tone = match ($application->status->value) {
            'passed_screening', 'shortlisted_exam', 'shortlisted_interview', 'selected' => 'green',
            'failed_screening', 'not_selected'                                        => 'red',
            'correction_required'                                                     => 'orange',
            'under_review', 'waitlisted'                                              => 'amber',
            'withdrawn'                                                               => 'gray',
            default                                                                   => 'blue',
        };
        $toneClasses = [
            'green'  => 'bg-green-50 text-green-700 ring-green-600/20',
            'red'    => 'bg-red-50 text-red-700 ring-red-600/20',
            'orange' => 'bg-orange-50 text-orange-700 ring-orange-600/20',
            'amber'  => 'bg-amber-50 text-amber-800 ring-amber-600/20',
            'gray'   => 'bg-gray-100 text-gray-700 ring-gray-500/20',
            'blue'   => 'bg-blue-50 text-blue-700 ring-blue-600/20',
        ][$tone];
        $vacancyTitle = is_array($application->vacancy->title)
            ? ($application->vacancy->title[app()->getLocale()] ?? $application->vacancy->title['en'] ?? '')
            : ($application->vacancy->getTranslation('title', app()->getLocale(), false) ?: $application->vacancy->getTranslation('title', 'en', false));

        $currentValue = $application->status->value;
        $steps = [
            ['status' => ['submitted', 'under_review', 'correction_required', 'passed_screening', 'failed_screening', 'shortlisted_exam', 'exam_completed', 'shortlisted_interview', 'interview_completed', 'selected', 'waitlisted', 'not_selected'], 'label' => __('applications.step_submitted'), 'icon' => 'file-text'],
            ['status' => ['passed_screening', 'failed_screening', 'shortlisted_exam', 'exam_completed', 'shortlisted_interview', 'interview_completed', 'selected', 'waitlisted', 'not_selected'], 'label' => __('applications.step_screened'), 'icon' => 'clipboard-check'],
            ['status' => ['shortlisted_exam', 'exam_completed', 'shortlisted_interview', 'interview_completed', 'selected', 'waitlisted', 'not_selected'], 'label' => __('applications.step_exam'), 'icon' => 'academic'],
            ['status' => ['shortlisted_interview', 'interview_completed', 'selected', 'waitlisted', 'not_selected'], 'label' => __('applications.step_interview'), 'icon' => 'users'],
            ['status' => ['selected', 'waitlisted', 'not_selected'], 'label' => __('applications.step_final'), 'icon' => 'check-circle'],
        ];
        $doneCount = collect($steps)->filter(fn ($s) => in_array($currentValue, $s['status'], true))->count();
        $isTerminalFail = in_array($currentValue, ['failed_screening', 'not_selected', 'withdrawn'], true);
    @endphp

    <section class="mt-8 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-card" aria-labelledby="result-heading" aria-live="polite">
        <div class="flex flex-wrap items-start justify-between gap-4 border-b border-gray-100 p-5 sm:p-6">
            <div class="min-w-0">
                <p class="text-xs font-medium text-gray-500">{{ __('applications.reference_number') }}</p>
                <h2 id="result-heading" class="mt-0.5 font-mono text-lg font-bold text-gray-900">{{ $application->reference_number }}</h2>
            </div>
            <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-semibold ring-1 ring-inset {{ $toneClasses }}">
                <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                {{ app()->getLocale() === 'am' ? $application->status->labelAmharic() : $application->status->label() }}
            </span>
        </div>

        <dl class="grid gap-4 border-b border-gray-100 p-5 sm:grid-cols-2 sm:p-6">
            <div>
                <dt class="text-xs font-medium text-gray-500">{{ __('vacancies.position') }}</dt>
                <dd class="mt-0.5 text-sm font-semibold text-gray-900">{{ $vacancyTitle }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-500">{{ __('applications.submitted_at') }}</dt>
                <dd class="mt-0.5 text-sm font-semibold text-gray-900">{{ $application->submitted_at ? et_date($application->submitted_at, 'd M Y H:i') : '—' }}</dd>
            </div>
        </dl>

        @if($application->status === \App\Enums\ApplicationStatus::CorrectionRequired && $application->screening_remark)
        <div class="border-b border-gray-100 p-5 sm:p-6">
            <div class="rounded-xl bg-orange-50 p-4 ring-1 ring-orange-200">
                <p class="flex items-center gap-2 text-sm font-semibold text-orange-800">
                    <x-public.icon name="alert" />
                    {{ __('applications.correction_required_note') }}
                </p>
                <p class="mt-2 text-sm text-orange-800/90">{{ $application->screening_remark }}</p>
                <a href="{{ route('login') }}"
                   class="mt-3 inline-flex items-center gap-1.5 rounded-lg bg-orange-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-orange-700">
                    {{ __('applications.login_to_update') }}
                    <x-public.icon name="arrow-right" class="h-3.5 w-3.5" />
                </a>
            </div>
        </div>
        @endif

        {{-- Progress --}}
        <div class="p-5 sm:p-6">
            <p class="mb-5 text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('applications.progress') }}</p>
            <ol class="grid gap-4 sm:grid-cols-5 sm:gap-0">
                @foreach($steps as $step)
                @php
                    $done      = in_array($currentValue, $step['status'], true);
                    $isCurrent = $loop->iteration === $doneCount;
                    $failHere  = $isCurrent && $isTerminalFail;
                    $circle = $failHere ? 'bg-red-600 text-white ring-red-100'
                        : ($done ? 'bg-brand text-white ring-brand/15' : 'bg-white text-gray-400 ring-gray-100 border border-gray-200');
                @endphp
                <li class="relative flex items-center gap-3 sm:flex-col sm:gap-2 sm:text-center" @if($isCurrent) aria-current="step" @endif>
                    @if(!$loop->last)
                    <span class="absolute left-4 top-9 h-[calc(100%-1rem)] w-0.5 sm:left-1/2 sm:top-4 sm:h-0.5 sm:w-full {{ $loop->iteration < $doneCount ? 'bg-brand' : 'bg-gray-200' }}" aria-hidden="true"></span>
                    @endif
                    <span class="relative z-10 flex h-8 w-8 shrink-0 items-center justify-center rounded-full ring-4 {{ $circle }}">
                        @if($failHere)
                            <x-public.icon name="x" class="h-4 w-4" />
                        @elseif($done)
                            <x-public.icon name="check" class="h-4 w-4" />
                        @else
                            <x-public.icon :name="$step['icon']" class="h-4 w-4" />
                        @endif
                    </span>
                    <span class="text-xs sm:px-1 {{ $done ? 'font-semibold text-gray-900' : 'text-gray-400' }}">{{ $step['label'] }}</span>
                </li>
                @endforeach
            </ol>
        </div>
    </section>
    @else
    {{-- Help --}}
    <div class="mt-8 grid gap-4 sm:grid-cols-2">
        <div class="flex items-start gap-3 rounded-2xl border border-gray-200 bg-white p-5">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-muted text-brand"><x-public.icon name="info" /></span>
            <div>
                <p class="text-sm font-semibold text-gray-900">{{ __('public.track_help_title') }}</p>
                <p class="mt-1 text-sm text-gray-500">{{ __('public.track_help_desc') }}</p>
            </div>
        </div>
        <div class="flex items-start gap-3 rounded-2xl border border-gray-200 bg-white p-5">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-accent-muted text-accent"><x-public.icon name="login" /></span>
            <div>
                <p class="text-sm font-semibold text-gray-900">{{ __('public.track_login_title') }}</p>
                <p class="mt-1 text-sm text-gray-500">{{ __('public.track_login_desc') }}</p>
                <a href="{{ route('login') }}" class="mt-2 inline-flex items-center gap-1 text-sm font-semibold text-brand hover:underline">
                    {{ __('menus.login') }} <x-public.icon name="arrow-right" class="h-3.5 w-3.5" />
                </a>
            </div>
        </div>
    </div>
    @endisset
</div>
@endsection
