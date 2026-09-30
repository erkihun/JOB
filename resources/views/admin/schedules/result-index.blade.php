@extends('layouts.admin')

@section('title', __('menus.exam_interview_scores'))

@section('content')
@php $typeTone = ['exam' => 'info', 'practical' => 'warning', 'interview' => 'success']; @endphp
<div class="space-y-6">
    <x-admin.page-header :title="__('menus.exam_interview_scores')"
                         :description="__('messages.select_schedule_to_record_results')"
                         :crumbs="[['label' => __('menus.exams_interviews')], ['label' => __('menus.exam_interview_scores')]]">
        <a href="{{ route('admin.schedules.index') }}" class="btn btn-secondary">{{ __('menus.schedules') }}</a>
    </x-admin.page-header>

    <x-admin.table-card :meta="trans_choice('messages.records_count', $schedules->total(), ['count' => number_format($schedules->total())])">
        <table class="min-w-full divide-y divide-gray-100 text-sm">
            <thead class="table-header">
                <tr>
                    <th class="table-th">{{ __('messages.title') }}</th>
                    <th class="table-th">{{ __('dashboard.table.type') }}</th>
                    <th class="table-th hidden md:table-cell">{{ __('menus.vacancies') }}</th>
                    <th class="table-th">{{ __('dashboard.table.date') }}</th>
                    <th class="table-th-right">{{ __('dashboard.assigned') }}</th>
                    <th class="table-th-right"><span class="sr-only">{{ __('messages.actions') }}</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($schedules as $schedule)
                <tr class="table-row">
                    <td class="table-td font-semibold text-gray-900">{{ $schedule->title }}</td>
                    <td class="table-td"><x-admin.status :tone="$typeTone[$schedule->type->value] ?? 'gray'" :label="$schedule->type->getLabel()" /></td>
                    <td class="table-td table-td-muted hidden md:table-cell">{{ $schedule->vacancy?->title }}</td>
                    <td class="table-td tabular-nums text-gray-800">{{ et_date($schedule->date) }} <span class="text-gray-600">· {{ $schedule->start_time }}</span></td>
                    <td class="table-td text-right tabular-nums">{{ $schedule->assigned_applicants_count }}</td>
                    <td class="table-td"><div class="table-actions"><a href="{{ route('admin.schedules.results', $schedule) }}" class="btn btn-primary btn-sm">{{ __('messages.record_results') }}</a></div></td>
                </tr>
                @empty
                <tr><td colspan="6"><x-admin.empty :text="__('messages.results_empty_hint')" /></td></tr>
                @endforelse
            </tbody>
        </table>
        <x-slot:footer>{{ $schedules->links() }}</x-slot:footer>
    </x-admin.table-card>
</div>
@endsection
