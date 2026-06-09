@extends('layouts.applicant')

@section('title', __('applicant.application_detail'))

@section('content')
<div class="space-y-6">

    {{-- Page header --}}
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-gray-900">
                {{ $application->vacancy->getTranslation('title', app()->getLocale(), false)
                   ?: $application->vacancy->getTranslation('title', 'en', false) }}
            </h1>
            <p class="mt-0.5 text-sm text-gray-400 font-mono">{{ $application->reference_number }}</p>
        </div>
        <div class="flex items-center gap-3 shrink-0">
            @php
                $colorMap = [
                    'success' => 'bg-green-100 text-green-800 border-green-200',
                    'danger'  => 'bg-red-100 text-red-800 border-red-200',
                    'warning' => 'bg-amber-100 text-amber-800 border-amber-200',
                    'info'    => 'bg-blue-100 text-blue-800 border-blue-200',
                ];
                $badge = $colorMap[$application->status->color()] ?? 'bg-gray-100 text-gray-800 border-gray-200';
            @endphp
            <span class="rounded-full border px-3 py-1 text-xs font-semibold {{ $badge }}">
                {{ $application->status->label() }}
            </span>
            @if($application->isEditable())
            <a href="{{ route('applicant.applications.edit', $application) }}"
               class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition">
                {{ __('applicant.edit_application') }}
            </a>
            @endif
        </div>
    </div>

    {{-- ── Status Timeline ── --}}
    @php
        use App\Enums\ApplicationStatus;

        $timelineSteps = [
            [
                'key'    => 'submitted',
                'label'  => __('applicant.timeline_submitted'),
                'statuses' => [
                    ApplicationStatus::Submitted,
                    ApplicationStatus::CorrectionRequired,
                ],
            ],
            [
                'key'    => 'review',
                'label'  => __('applicant.timeline_review'),
                'statuses' => [
                    ApplicationStatus::UnderReview,
                ],
            ],
            [
                'key'    => 'screening',
                'label'  => __('applicant.timeline_screening'),
                'statuses' => [
                    ApplicationStatus::PassedScreening,
                    ApplicationStatus::FailedScreening,
                ],
            ],
            [
                'key'    => 'exam',
                'label'  => __('applicant.timeline_exam'),
                'statuses' => [
                    ApplicationStatus::ShortlistedExam,
                    ApplicationStatus::ExamCompleted,
                    ApplicationStatus::ShortlistedInterview,
                    ApplicationStatus::InterviewCompleted,
                ],
            ],
            [
                'key'    => 'result',
                'label'  => __('applicant.timeline_result'),
                'statuses' => [
                    ApplicationStatus::Selected,
                    ApplicationStatus::NotSelected,
                    ApplicationStatus::Waitlisted,
                    ApplicationStatus::Withdrawn,
                ],
            ],
        ];

        // Determine which step index the current status falls in
        $currentStepIndex = 0;
        foreach ($timelineSteps as $idx => $step) {
            foreach ($step['statuses'] as $s) {
                if ($application->status === $s) {
                    $currentStepIndex = $idx;
                    break 2;
                }
            }
        }

        // Determine if current status is a terminal negative
        $isTerminalNegative = in_array($application->status, [
            ApplicationStatus::FailedScreening,
            ApplicationStatus::NotSelected,
            ApplicationStatus::Withdrawn,
        ]);
    @endphp

    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="border-b border-gray-100 bg-gray-50 px-5 py-3.5 flex items-center gap-2">
            <svg class="h-4 w-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            <h2 class="text-sm font-semibold text-gray-700">{{ __('applicant.status_timeline') }}</h2>
        </div>

        {{-- Desktop: horizontal timeline --}}
        <div class="hidden sm:block px-8 py-7">
            <div class="relative flex items-start">

                {{-- Connector line --}}
                <div class="absolute top-5 left-0 right-0 flex">
                    @foreach($timelineSteps as $idx => $step)
                    @if(!$loop->last)
                    @php
                        $lineCompleted = $idx < $currentStepIndex;
                    @endphp
                    <div class="flex-1 mx-1">
                        <div class="h-0.5 w-full {{ $lineCompleted ? 'bg-blue-500' : 'bg-gray-200' }} transition-colors"></div>
                    </div>
                    @endif
                    @endforeach
                </div>

                {{-- Steps --}}
                @foreach($timelineSteps as $idx => $step)
                @php
                    $isCompleted = $idx < $currentStepIndex;
                    $isCurrent   = $idx === $currentStepIndex;
                    $isPending   = $idx > $currentStepIndex;

                    if ($isCurrent && $isTerminalNegative) {
                        $circleClass = 'bg-red-500 ring-4 ring-red-100';
                        $iconPath    = 'M6 18L18 6M6 6l12 12';
                        $labelClass  = 'text-red-600 font-bold';
                    } elseif ($isCompleted) {
                        $circleClass = 'bg-green-500 ring-4 ring-green-100';
                        $iconPath    = 'M5 13l4 4L19 7';
                        $labelClass  = 'text-green-700 font-semibold';
                    } elseif ($isCurrent) {
                        $circleClass = 'bg-blue-600 ring-4 ring-blue-100';
                        $iconPath    = null; // number
                        $labelClass  = 'text-blue-700 font-bold';
                    } else {
                        $circleClass = 'bg-gray-200 ring-4 ring-gray-50';
                        $iconPath    = null; // number
                        $labelClass  = 'text-gray-400';
                    }
                @endphp
                <div class="relative flex-1 flex flex-col items-center">
                    {{-- Circle --}}
                    <div class="relative z-10 flex h-10 w-10 items-center justify-center rounded-full {{ $circleClass }} shadow-sm transition-all">
                        @if($isCompleted || ($isCurrent && $isTerminalNegative))
                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="{{ $iconPath }}"/>
                        </svg>
                        @else
                        <span class="text-xs font-bold {{ $isCurrent ? 'text-white' : 'text-gray-400' }}">{{ $idx + 1 }}</span>
                        @endif
                    </div>
                    {{-- Label --}}
                    <p class="mt-2.5 text-center text-xs {{ $labelClass }} max-w-20 leading-snug">{{ $step['label'] }}</p>
                    {{-- Date (if submitted and available) --}}
                    @if($isCurrent && $application->submitted_at && $idx === 0)
                    <p class="mt-1 text-center text-[10px] text-gray-400">{{ et_date($application->submitted_at, 'M d') }}</p>
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        {{-- Mobile: vertical timeline --}}
        <div class="sm:hidden px-5 py-5">
            <ol class="relative border-l-2 border-gray-200 ml-3 space-y-5">
                @foreach($timelineSteps as $idx => $step)
                @php
                    $isCompleted = $idx < $currentStepIndex;
                    $isCurrent   = $idx === $currentStepIndex;

                    if ($isCurrent && $isTerminalNegative) {
                        $dotClass   = 'bg-red-500';
                        $labelClass = 'font-bold text-red-700';
                    } elseif ($isCompleted) {
                        $dotClass   = 'bg-green-500';
                        $labelClass = 'font-semibold text-green-700';
                    } elseif ($isCurrent) {
                        $dotClass   = 'bg-blue-600';
                        $labelClass = 'font-bold text-blue-700';
                    } else {
                        $dotClass   = 'bg-gray-300';
                        $labelClass = 'text-gray-400';
                    }
                @endphp
                <li class="ml-5">
                    <span class="absolute -left-1.5 flex h-5 w-5 items-center justify-center rounded-full {{ $dotClass }} ring-2 ring-white">
                        @if($isCompleted)
                        <svg class="h-3 w-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                        </svg>
                        @elseif($isCurrent && $isTerminalNegative)
                        <svg class="h-3 w-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        @else
                        <span class="text-[9px] font-bold {{ $isCurrent ? 'text-white' : 'text-gray-500' }}">{{ $idx + 1 }}</span>
                        @endif
                    </span>
                    <p class="text-sm {{ $labelClass }}">{{ $step['label'] }}</p>
                    @if($isCurrent && $application->submitted_at && $idx === 0)
                    <p class="text-xs text-gray-400">{{ et_date($application->submitted_at, 'M d, Y') }}</p>
                    @endif
                </li>
                @endforeach
            </ol>
        </div>
    </div>

    {{-- ── Application Details ── --}}
    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="border-b border-gray-100 bg-gray-50 px-5 py-3.5">
            <h2 class="text-sm font-semibold text-gray-700">{{ __('applicant.application_detail') }}</h2>
        </div>
        @php
            $fields = [
                [__('applications.reference_number'), $application->reference_number, true],
                [__('applicant.submitted_at'),        $application->submitted_at ? et_date($application->submitted_at, 'M d, Y H:i') : '—', false],
                [__('fields.field_of_study'),         $application->field_of_study, false],
                [__('fields.graduation_date'),        $application->graduation_date ? et_date($application->graduation_date, 'd M Y') : '—', false],
                [__('fields.cgpa'),                   $application->cgpa !== null ? number_format($application->cgpa, 2) : null, false],
            ];
        @endphp
        @foreach($fields as [$label, $value, $mono])
        @if($value !== null)
        <div class="flex items-start border-b border-gray-50 last:border-b-0 px-5 py-3.5 gap-4">
            <dt class="w-44 shrink-0 text-sm font-medium text-gray-500">{{ $label }}</dt>
            <dd class="text-sm text-gray-900 {{ $mono ? 'font-mono' : '' }}">{{ $value }}</dd>
        </div>
        @endif
        @endforeach
    </div>

    {{-- ── Documents ── --}}
    @if($application->documents->isNotEmpty())
    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="border-b border-gray-100 bg-gray-50 px-5 py-3.5">
            <h2 class="text-sm font-semibold text-gray-700">{{ __('applicant.uploaded_documents') }}</h2>
        </div>
        <div class="divide-y divide-gray-100">
            @foreach($application->documents as $document)
            @php
                $verColor = match($document->verification_status->value ?? '') {
                    'verified'  => 'bg-green-100 text-green-800 border-green-200',
                    'rejected'  => 'bg-red-100 text-red-800 border-red-200',
                    default     => 'bg-gray-100 text-gray-700 border-gray-200',
                };
                $docName = $document->vacancyDocument?->document_name
                           ?: $document->original_name;
            @endphp
            <div class="flex items-center justify-between px-5 py-3.5 gap-4">
                <div class="min-w-0 flex items-start gap-3">
                    <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-50">
                        <svg class="h-4 w-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ $docName }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">
                            {{ $document->original_name }} · {{ number_format($document->file_size / 1024, 1) }} KB
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    <span class="rounded-full border px-2.5 py-0.5 text-xs font-medium {{ $verColor }}">
                        {{ $document->verification_status->label() }}
                    </span>
                    <a href="{{ route('applicant.documents.download', $document) }}"
                       class="inline-flex items-center gap-1 text-sm text-blue-600 hover:text-blue-800 font-medium transition">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <div>
        <a href="{{ route('applicant.applications.index') }}"
           class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800 transition">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            {{ __('applicant.back_to_applications') }}
        </a>
    </div>
</div>
@endsection
