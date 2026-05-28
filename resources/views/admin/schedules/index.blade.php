@extends('layouts.admin')
@section('title', __('menus.schedules'))
@section('content')
<div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-lg font-semibold text-gray-900">{{ __('menus.schedules') }}</h1>
        <a href="{{ route('admin.schedules.create') }}" class="btn btn-primary">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            {{ __('messages.add_schedule') }}
        </a>
    </div>

    <form method="GET" class="flex flex-wrap gap-2">
        <select name="vacancy_id" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand focus:ring-1 focus:ring-brand">
            <option value="">{{ __('messages.all_vacancies') }}</option>
            @foreach($vacancies as $v)
            <option value="{{ $v->id }}" {{ request('vacancy_id') === $v->id ? 'selected' : '' }}>{{ $v->code }} — {{ $v->title }}</option>
            @endforeach
        </select>
        <select name="type" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand focus:ring-1 focus:ring-brand">
            <option value="">{{ __('messages.all_types') }}</option>
            @foreach($types as $t)
            <option value="{{ $t->value }}" {{ request('type') === $t->value ? 'selected' : '' }}>{{ $t->getLabel() }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-navy">{{ __('messages.filter') }}</button>
    </form>

    <div class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-100 text-sm">
            <thead class="table-header">
                <tr>
                    <th class="table-th">{{ __('messages.title') }}</th>
                    <th class="table-th">{{ __('dashboard.table.type') }}</th>
                    <th class="table-th hidden sm:table-cell">{{ __('menus.vacancies') }}</th>
                    <th class="table-th">{{ __('dashboard.table.date') }}</th>
                    <th class="table-th hidden md:table-cell">{{ __('dashboard.table.venue') }}</th>
                    <th class="table-th-right">{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($schedules as $schedule)
                @php
                $typeBadge = $schedule->type->value === 'exam' ? 'badge-blue' : 'badge-amber';
                $canRecordResults = auth()->user()->hasAnyRole(['super_admin', 'admin', 'hr_manager'])
                    || auth()->user()->hasPermissionTo($schedule->type->value === 'exam' ? 'exams.record-results' : 'interviews.record-results');
                @endphp
                <tr class="table-row">
                    <td class="table-td font-medium text-gray-900">{{ $schedule->title }}</td>
                    <td class="table-td"><span class="{{ $typeBadge }}">{{ $schedule->type->getLabel() }}</span></td>
                    <td class="table-td hidden text-gray-600 sm:table-cell">{{ $schedule->vacancy?->title }}</td>
                    <td class="table-td text-gray-700">{{ et_date($schedule->date) }} {{ $schedule->start_time }}</td>
                    <td class="table-td hidden text-gray-500 md:table-cell">{{ $schedule->venue ?? '—' }}</td>
                    <td class="table-td text-right">
                        <div class="flex items-center justify-end gap-3">
                            @if($canRecordResults)
                            <a href="{{ route('admin.schedules.results', $schedule) }}" class="text-xs font-medium text-green-600 hover:text-green-800">{{ __('messages.results') }}</a>
                            @endif
                            <a href="{{ route('admin.schedules.edit', $schedule) }}" class="text-xs font-medium text-brand hover:text-brand-dark">{{ __('messages.edit') }}</a>
                            <form method="POST" action="{{ route('admin.schedules.destroy', $schedule) }}" onsubmit="return confirm('{{ __('messages.confirm_delete') }}')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs font-medium text-red-500 hover:text-red-700">{{ __('messages.delete') }}</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-10 text-center text-gray-400">{{ __('messages.no_records') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($schedules->hasPages())
        <div class="border-t border-gray-100 px-4 py-3">{{ $schedules->links() }}</div>
        @endif
    </div>
</div>
@endsection
