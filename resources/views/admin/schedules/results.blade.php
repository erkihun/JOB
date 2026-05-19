@extends('layouts.admin')

@section('title', __('messages.record_results').': '.$schedule->title)

@section('content')
<div class="space-y-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <a href="{{ route('admin.schedules.index') }}" class="text-sm font-medium text-brand hover:text-brand-dark">
                {{ __('messages.back') }}
            </a>
            <h1 class="mt-2 text-lg font-semibold text-gray-900">{{ __('messages.record_results') }}</h1>
            <p class="mt-1 text-sm text-gray-500">
                {{ $schedule->title }} · {{ $schedule->type->getLabel() }} · {{ $schedule->date?->format('d M Y') }} {{ $schedule->start_time }}
            </p>
        </div>
        <span class="{{ $schedule->type->value === 'exam' ? 'badge-blue' : 'badge-amber' }}">
            {{ $schedule->type->getLabel() }}
        </span>
    </div>

    <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
        <div class="grid gap-4 text-sm text-gray-600 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('menus.vacancies') }}</p>
                <p class="mt-1 font-medium text-gray-900">{{ $schedule->vacancy?->code }} · {{ $schedule->vacancy?->title }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('dashboard.table.venue') }}</p>
                <p class="mt-1 font-medium text-gray-900">{{ $schedule->venue }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('messages.total_records') }}</p>
                <p class="mt-1 font-medium text-gray-900">{{ $schedule->assignedApplicants->count() }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('messages.instructions') }}</p>
                <p class="mt-1 line-clamp-2">{{ $schedule->instruction ?: '—' }}</p>
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-base font-semibold text-gray-900">{{ __('messages.assign_applicants') }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ __('messages.assign_applicants_hint') }}</p>
            </div>
            <span class="badge-gray">{{ $eligibleApplications->count() }} {{ __('dashboard.applicants') }}</span>
        </div>

        @if($eligibleApplications->isNotEmpty())
        <form method="POST" action="{{ route('admin.schedules.applicants.assign', $schedule) }}" class="mt-4 space-y-4">
            @csrf
            <div class="max-h-80 overflow-y-auto rounded-lg border border-gray-100">
                @foreach($eligibleApplications as $application)
                @php $applicant = $application->applicant; @endphp
                <label class="flex cursor-pointer items-start gap-3 border-b border-gray-50 px-4 py-3 last:border-b-0 hover:bg-blue-50/40">
                    <input type="checkbox" name="application_ids[]" value="{{ $application->id }}" class="mt-1 h-4 w-4 rounded border-gray-300 text-brand focus:ring-brand">
                    <span class="min-w-0">
                        <span class="block text-sm font-medium text-gray-900">{{ $applicant?->full_name ?? '—' }}</span>
                        <span class="mt-0.5 block text-xs text-gray-500">
                            {{ $application->reference_number }} · {{ $applicant?->email ?? $applicant?->phone ?? '—' }} · {{ $application->status->label() }}
                        </span>
                    </span>
                </label>
                @endforeach
            </div>
            @error('application_ids')
                <p class="text-xs text-red-600">{{ $message }}</p>
            @enderror
            <button type="submit" class="btn btn-primary">{{ __('messages.assign_selected') }}</button>
        </form>
        @else
        <div class="mt-4 rounded-lg border border-dashed border-gray-200 px-4 py-8 text-center text-sm text-gray-400">
            {{ __('messages.no_eligible_applicants') }}
        </div>
        @endif
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-100 text-sm">
            <thead class="table-header">
                <tr>
                    <th class="table-th">{{ __('messages.applicant') }}</th>
                    <th class="table-th">{{ __('messages.reference') }}</th>
                    <th class="table-th">{{ __('applications.status') }}</th>
                    <th class="table-th">{{ __('messages.score') }}</th>
                    <th class="table-th">{{ __('messages.remarks') }}</th>
                    <th class="table-th-right">{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($schedule->assignedApplicants as $record)
                @php
                    $application = $record->application;
                    $applicant = $application?->applicant;
                    $statusOptions = [
                        'invited' => __('messages.invited'),
                        'attended' => __('messages.attended'),
                        'absent' => __('messages.absent'),
                        'passed' => __('messages.pass'),
                        'failed' => __('messages.fail'),
                    ];
                @endphp
                <tr class="table-row align-top">
                    <td class="table-td">
                        <p class="font-medium text-gray-900">{{ $applicant?->full_name ?? '—' }}</p>
                        <p class="mt-0.5 text-xs text-gray-400">{{ $applicant?->email ?? $applicant?->phone }}</p>
                    </td>
                    <td class="table-td font-mono text-xs text-gray-600">{{ $application?->reference_number ?? '—' }}</td>
                    <td class="table-td">
                        <select name="status" form="result-form-{{ $record->id }}" class="form-select min-w-32">
                            @foreach($statusOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $record->status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="table-td">
                        <input
                            type="number"
                            name="score"
                            form="result-form-{{ $record->id }}"
                            value="{{ old('score', $record->score) }}"
                            min="0"
                            max="100"
                            step="0.01"
                            class="form-input w-28"
                            placeholder="0.00"
                        >
                    </td>
                    <td class="table-td">
                        <textarea
                            name="remark"
                            form="result-form-{{ $record->id }}"
                            rows="2"
                            class="form-textarea min-w-64"
                            placeholder="{{ __('messages.remarks_placeholder') }}"
                        >{{ old('remark', $record->remark) }}</textarea>
                    </td>
                    <td class="table-td text-right">
                        <form id="result-form-{{ $record->id }}" method="POST" action="{{ route('admin.schedules.results.store', [$schedule, $record]) }}">
                            @csrf
                        </form>
                        <button type="submit" form="result-form-{{ $record->id }}" class="btn btn-primary">
                            {{ __('messages.save_result') }}
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-10 text-center text-gray-400">{{ __('messages.no_records') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
