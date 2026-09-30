@extends('layouts.admin')

@section('title', __('messages.record_results').': '.$schedule->title)

@section('content')
@php
    $records = $schedule->assignedApplicants;
    $recorded = $records->whereIn('status', ['passed', 'failed', 'absent'])->count();
    $typeTone = ['exam' => 'info', 'practical' => 'warning', 'interview' => 'success'][$schedule->type->value] ?? 'gray';
    $statusOptions = [
        'invited'  => __('messages.invited'),
        'attended' => __('messages.attended'),
        'absent'   => __('messages.absent'),
        'passed'   => __('messages.pass'),
        'failed'   => __('messages.fail'),
    ];
@endphp
<div class="space-y-6">
    <x-admin.page-header :title="$schedule->title"
                         :description="$schedule->type->getLabel().' · '.et_date($schedule->date).' · '.$schedule->start_time.' · '.$schedule->venue"
                         :crumbs="[['label' => __('menus.exams_interviews')], ['label' => __('menus.exam_interview_scores'), 'url' => route('admin.schedule-results.index')], ['label' => __('messages.record_results')]]">
        <x-admin.status :tone="$typeTone" :label="$schedule->type->getLabel()" />
        <a href="{{ route('admin.schedules.edit', $schedule) }}" class="btn btn-secondary">{{ __('messages.edit') }}</a>
    </x-admin.page-header>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-admin.stat :label="__('dashboard.assigned')" :value="number_format($records->count())" />
        <x-admin.stat :label="__('messages.res_recorded')" :value="$recorded.' / '.$records->count()" :tone="$recorded < $records->count() ? 'warn' : 'good'"
                      :hint="$recorded < $records->count() ? __('messages.res_pending', ['count' => $records->count() - $recorded]) : __('messages.res_all_recorded')" />
        <x-admin.stat :label="__('messages.pass')" :value="number_format($records->where('status', 'passed')->count())" tone="good" />
        <x-admin.stat :label="__('messages.fail')" :value="number_format($records->where('status', 'failed')->count())" tone="bad" />
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem] xl:items-start">
        {{-- Results --}}
        <x-admin.table-card :title="__('messages.res_scores')" :meta="__('messages.res_scores_hint')">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="table-header">
                    <tr>
                        <th class="table-th">{{ __('messages.applicant') }}</th>
                        <th class="table-th">{{ __('applications.status') }}</th>
                        <th class="table-th">{{ __('messages.score') }}</th>
                        <th class="table-th">{{ __('messages.remarks') }}</th>
                        <th class="table-th-right"><span class="sr-only">{{ __('messages.actions') }}</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($records as $record)
                    @php $application = $record->application; $applicant = $application?->applicant; @endphp
                    <tr class="table-row align-top">
                        <td class="table-td">
                            <div class="flex items-center gap-3">
                                <x-admin.avatar :name="$applicant?->full_name" />
                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-gray-900">{{ $applicant?->full_name ?? '—' }}</p>
                                    <p class="font-mono text-xs text-gray-600">{{ $application?->reference_number ?? '—' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="table-td">
                            <label class="sr-only" for="status-{{ $record->id }}">{{ __('applications.status') }}</label>
                            <select id="status-{{ $record->id }}" name="status" form="result-form-{{ $record->id }}" class="form-select min-w-32">
                                @foreach($statusOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $record->status) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="table-td">
                            <label class="sr-only" for="score-{{ $record->id }}">{{ __('messages.score') }}</label>
                            <input id="score-{{ $record->id }}" type="number" name="score" form="result-form-{{ $record->id }}" value="{{ old('score', $record->score) }}"
                                   min="0" max="100" step="0.01" inputmode="decimal" class="form-input w-24 tabular-nums" placeholder="0–100">
                        </td>
                        <td class="table-td">
                            <label class="sr-only" for="remark-{{ $record->id }}">{{ __('messages.remarks') }}</label>
                            <textarea id="remark-{{ $record->id }}" name="remark" form="result-form-{{ $record->id }}" rows="2" class="form-textarea min-w-56"
                                      placeholder="{{ __('messages.remarks_placeholder') }}">{{ old('remark', $record->remark) }}</textarea>
                        </td>
                        <td class="table-td">
                            <form id="result-form-{{ $record->id }}" method="POST" action="{{ route('admin.schedules.results.store', [$schedule, $record]) }}">@csrf</form>
                            <div class="table-actions"><button type="submit" form="result-form-{{ $record->id }}" class="btn btn-primary btn-sm">{{ __('messages.save_result') }}</button></div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5"><x-admin.empty :text="__('messages.res_no_assigned')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-admin.table-card>

        {{-- Assign applicants --}}
        <aside class="space-y-6 xl:sticky xl:top-24">
            <section class="card" x-data="{ all: false }">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">{{ __('messages.assign_applicants') }}</h2>
                        <p class="card-description">{{ __('messages.assign_applicants_hint') }}</p>
                    </div>
                    <span class="badge badge-gray">{{ $eligibleApplications->count() }}</span>
                </div>
                @if($eligibleApplications->isNotEmpty())
                <form method="POST" action="{{ route('admin.schedules.applicants.assign', $schedule) }}">
                    @csrf
                    <label class="flex items-center gap-2 border-b border-gray-100 px-5 py-2.5 text-sm font-semibold text-gray-800">
                        <input type="checkbox" x-model="all" @change="$el.closest('form').querySelectorAll('input[name=\'application_ids[]\']').forEach(c => c.checked = all)" class="h-4 w-4 rounded border-gray-300 text-brand focus:ring-brand">
                        {{ __('messages.select_all') }}
                    </label>
                    <div class="max-h-80 overflow-y-auto">
                        @foreach($eligibleApplications as $application)
                        <label class="flex cursor-pointer items-start gap-3 border-b border-gray-50 px-5 py-2.5 last:border-b-0 hover:bg-gray-50">
                            <input type="checkbox" name="application_ids[]" value="{{ $application->id }}" class="mt-0.5 h-4 w-4 rounded border-gray-300 text-brand focus:ring-brand">
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-semibold text-gray-900">{{ $application->applicant?->full_name ?? '—' }}</span>
                                <span class="block text-xs text-gray-600">{{ $application->reference_number }} · {{ $application->status->label() }}</span>
                            </span>
                        </label>
                        @endforeach
                    </div>
                    @error('application_ids')<p class="form-error px-5">{{ $message }}</p>@enderror
                    <div class="card-footer"><button type="submit" class="btn btn-primary w-full justify-center">{{ __('messages.assign_selected') }}</button></div>
                </form>
                @else
                <p class="px-5 py-6 text-center text-sm text-gray-600">{{ __('messages.no_eligible_applicants') }}</p>
                @endif
            </section>

            <section class="card card-body">
                <h2 class="card-title mb-3">{{ __('vacancies.details') }}</h2>
                <dl class="space-y-3 text-sm">
                    <div><dt class="text-[13px] font-semibold text-gray-600">{{ __('menus.vacancies') }}</dt><dd class="mt-0.5 text-gray-900">{{ $schedule->vacancy?->code }} · {{ $schedule->vacancy?->title }}</dd></div>
                    <div><dt class="text-[13px] font-semibold text-gray-600">{{ __('dashboard.table.venue') }}</dt><dd class="mt-0.5 text-gray-900">{{ $schedule->venue }}</dd></div>
                    <div><dt class="text-[13px] font-semibold text-gray-600">{{ __('messages.instructions') }}</dt><dd class="mt-0.5 whitespace-pre-line text-gray-900">{{ $schedule->instruction ?: '—' }}</dd></div>
                </dl>
            </section>
        </aside>
    </div>
</div>
@endsection
