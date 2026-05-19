@extends('layouts.applicant')

@section('title', __('applicant.application_detail'))

@section('content')
<div class="space-y-6">
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
                    'success' => 'bg-green-100 text-green-800',
                    'danger'  => 'bg-red-100 text-red-800',
                    'warning' => 'bg-amber-100 text-amber-800',
                    'info'    => 'bg-blue-100 text-blue-800',
                ];
                $badge = $colorMap[$application->status->color()] ?? 'bg-gray-100 text-gray-800';
            @endphp
            <span class="rounded-full px-3 py-1 text-xs font-medium {{ $badge }}">
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

    {{-- Fields --}}
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="border-b border-gray-100 bg-gray-50 px-5 py-3">
            <h2 class="text-sm font-semibold text-gray-700">{{ __('applicant.application_detail') }}</h2>
        </div>
        @php
            $fields = [
                [__('applications.reference_number'), $application->reference_number, true],
                [__('applicant.submitted_at'),        $application->submitted_at?->format('M d, Y H:i'), false],
                [__('fields.field_of_study'),         $application->field_of_study, false],
                [__('fields.graduation_date'),        $application->graduation_date ? \Carbon\Carbon::parse($application->graduation_date)->format('M d, Y') : '—', false],
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

    {{-- Documents --}}
    @if($application->documents->isNotEmpty())
    <div>
        <h2 class="text-base font-semibold text-gray-900 mb-3">{{ __('applicant.uploaded_documents') }}</h2>
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm divide-y divide-gray-100">
            @foreach($application->documents as $document)
            @php
                $verColor = match($document->verification_status->value ?? '') {
                    'verified'  => 'bg-green-100 text-green-800',
                    'rejected'  => 'bg-red-100 text-red-800',
                    default     => 'bg-gray-100 text-gray-700',
                };
                $docName = $document->vacancyDocument?->getTranslation('document_name', app()->getLocale(), false)
                           ?: $document->vacancyDocument?->document_name
                           ?: $document->original_name;
            @endphp
            <div class="flex items-center justify-between px-5 py-3.5 gap-4">
                <div class="min-w-0">
                    <p class="text-sm font-medium text-gray-900">{{ $docName }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">
                        {{ $document->original_name }} · {{ number_format($document->file_size / 1024, 1) }} KB
                    </p>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $verColor }}">
                        {{ $document->verification_status->label() }}
                    </span>
                    <a href="{{ route('applicant.documents.download', $document) }}"
                       class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                        ↓
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <div>
        <a href="{{ route('applicant.applications.index') }}" class="text-sm text-gray-500 hover:text-gray-800">
            {{ __('applicant.back_to_applications') }}
        </a>
    </div>
</div>
@endsection
