@extends('layouts.admin')

@section('title', __('menus.exam_interview_scores'))

@section('content')
<div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900">{{ __('menus.exam_interview_scores') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('messages.select_schedule_to_record_results') }}</p>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-100 text-sm">
            <thead class="table-header">
                <tr>
                    <th class="table-th">{{ __('messages.title') }}</th>
                    <th class="table-th">{{ __('dashboard.table.type') }}</th>
                    <th class="table-th hidden sm:table-cell">{{ __('menus.vacancies') }}</th>
                    <th class="table-th">{{ __('dashboard.table.date') }}</th>
                    <th class="table-th">{{ __('dashboard.assigned') }}</th>
                    <th class="table-th-right">{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($schedules as $schedule)
                @php
                    $typeBadge = $schedule->type->value === 'exam' ? 'badge-blue' : 'badge-amber';
                @endphp
                <tr class="table-row">
                    <td class="table-td font-medium text-gray-900">{{ $schedule->title }}</td>
                    <td class="table-td"><span class="{{ $typeBadge }}">{{ $schedule->type->getLabel() }}</span></td>
                    <td class="table-td hidden text-gray-600 sm:table-cell">{{ $schedule->vacancy?->title }}</td>
                    <td class="table-td text-gray-700">{{ et_date($schedule->date) }} {{ $schedule->start_time }}</td>
                    <td class="table-td text-gray-700">{{ $schedule->assigned_applicants_count }}</td>
                    <td class="table-td text-right">
                        <a href="{{ route('admin.schedules.results', $schedule) }}" class="btn btn-primary">
                            {{ __('messages.record_results') }}
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-10 text-center text-gray-400">{{ __('messages.no_records') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($schedules->hasPages())
        <div class="border-t border-gray-100 px-4 py-3">{{ $schedules->links() }}</div>
        @endif
    </div>
</div>
@endsection
