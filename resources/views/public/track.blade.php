@extends('layouts.public')

@section('title', __('applications.track_title'))

@section('content')
<div class="mx-auto max-w-2xl px-4 py-12 sm:px-6 lg:px-8">

    <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ __('applications.track_title') }}</h1>
    <p class="text-gray-500 mb-8 text-sm">{{ __('applications.track_subtitle') }}</p>

    {{-- Search Form --}}
    <form method="POST" action="{{ route('track.search') }}" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
        @csrf
        <div class="space-y-4">
            <div>
                <label for="reference_number" class="block text-sm font-medium text-gray-700 mb-1">
                    {{ __('applications.reference_number') }}
                </label>
                <input type="text" id="reference_number" name="reference_number"
                       value="{{ old('reference_number') }}"
                       placeholder="APP-2024-000001"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 @error('reference_number') border-red-400 @enderror">
                @error('reference_number')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="identifier" class="block text-sm font-medium text-gray-700 mb-1">
                    {{ __('applications.track_identifier_label') }}
                </label>
                <input type="text" id="identifier" name="identifier"
                       value="{{ old('identifier') }}"
                       placeholder="{{ __('applications.track_identifier_placeholder') }}"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 @error('identifier') border-red-400 @enderror">
                @error('identifier')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                    class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 transition">
                {{ __('applications.track_submit') }}
            </button>
        </div>
    </form>

    {{-- Result --}}
    @isset($application)
    @php
        $statusColors = [
            'submitted'            => 'bg-blue-100 text-blue-800',
            'under_review'         => 'bg-yellow-100 text-yellow-800',
            'correction_required'  => 'bg-orange-100 text-orange-800',
            'passed_screening'     => 'bg-green-100 text-green-800',
            'failed_screening'     => 'bg-red-100 text-red-800',
            'shortlisted_exam'     => 'bg-green-100 text-green-800',
            'exam_completed'       => 'bg-blue-100 text-blue-800',
            'shortlisted_interview'=> 'bg-green-100 text-green-800',
            'interview_completed'  => 'bg-blue-100 text-blue-800',
            'selected'             => 'bg-green-100 text-green-800',
            'waitlisted'           => 'bg-yellow-100 text-yellow-800',
            'not_selected'         => 'bg-red-100 text-red-800',
            'withdrawn'            => 'bg-gray-100 text-gray-700',
        ];
        $color = $statusColors[$application->status->value] ?? 'bg-gray-100 text-gray-700';
        $vacancyTitle = is_array($application->vacancy->title) ? ($application->vacancy->title[app()->getLocale()] ?? $application->vacancy->title['en'] ?? '') : $application->vacancy->title;
    @endphp

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-blue-600 px-6 py-4">
            <p class="text-xs text-blue-200 uppercase tracking-wider font-medium">{{ __('applications.reference_number') }}</p>
            <p class="text-xl font-bold text-white mt-1">{{ $application->reference_number }}</p>
        </div>

        <div class="p-6 space-y-4">
            {{-- Status --}}
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-500">{{ __('applications.status') }}</span>
                <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-semibold {{ $color }}">
                    {{ app()->getLocale() === 'am' ? $application->status->labelAmharic() : $application->status->label() }}
                </span>
            </div>

            <div class="border-t border-gray-100 pt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <p class="text-xs text-gray-500 uppercase font-medium">{{ __('vacancies.position') }}</p>
                    <p class="text-sm font-semibold text-gray-900 mt-0.5">{{ $vacancyTitle }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase font-medium">{{ __('applications.submitted_at') }}</p>
                    <p class="text-sm text-gray-700 mt-0.5">{{ $application->submitted_at ? et_date($application->submitted_at, 'd M Y H:i') : '—' }}</p>
                </div>
            </div>

            {{-- Correction Required message --}}
            @if($application->status === \App\Enums\ApplicationStatus::CorrectionRequired && $application->screening_remark)
            <div class="rounded-lg bg-orange-50 border border-orange-200 p-4">
                <p class="text-sm font-medium text-orange-800 mb-1">{{ __('applications.correction_required_note') }}</p>
                <p class="text-sm text-orange-700">{{ $application->screening_remark }}</p>
                <a href="{{ route('login') }}"
                   class="mt-2 inline-block text-sm font-medium text-orange-800 underline hover:text-orange-900">
                    {{ __('applications.login_to_update') }}
                </a>
            </div>
            @endif

            {{-- Progress Timeline --}}
            <div class="border-t border-gray-100 pt-4">
                <p class="text-xs font-medium text-gray-500 uppercase mb-3">{{ __('applications.progress') }}</p>
                @php
                    $steps = [
                        ['status' => ['submitted', 'under_review', 'correction_required', 'passed_screening', 'failed_screening', 'shortlisted_exam', 'exam_completed', 'shortlisted_interview', 'interview_completed', 'selected', 'waitlisted', 'not_selected'], 'label' => __('applications.step_submitted')],
                        ['status' => ['passed_screening', 'failed_screening', 'shortlisted_exam', 'exam_completed', 'shortlisted_interview', 'interview_completed', 'selected', 'waitlisted', 'not_selected'], 'label' => __('applications.step_screened')],
                        ['status' => ['shortlisted_exam', 'exam_completed', 'shortlisted_interview', 'interview_completed', 'selected', 'waitlisted', 'not_selected'], 'label' => __('applications.step_exam')],
                        ['status' => ['shortlisted_interview', 'interview_completed', 'selected', 'waitlisted', 'not_selected'], 'label' => __('applications.step_interview')],
                        ['status' => ['selected', 'waitlisted', 'not_selected'], 'label' => __('applications.step_final')],
                    ];
                    $currentValue = $application->status->value;
                @endphp
                <ol class="flex items-center gap-1 flex-wrap">
                    @foreach($steps as $step)
                    @php $done = in_array($currentValue, $step['status'], true); @endphp
                    <li class="flex items-center">
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold
                                     {{ $done ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-500' }}">
                            {{ $loop->index + 1 }}
                        </span>
                        <span class="ml-1 text-xs {{ $done ? 'text-gray-800 font-medium' : 'text-gray-400' }}">
                            {{ $step['label'] }}
                        </span>
                        @if(!$loop->last)
                        <span class="mx-2 text-gray-300 text-sm">›</span>
                        @endif
                    </li>
                    @endforeach
                </ol>
            </div>
        </div>
    </div>
    @endisset

</div>
@endsection
