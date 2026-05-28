@extends('layouts.admin')
@section('title', __('menus.vacancies'))

@section('content')
<div class="space-y-4">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-lg font-semibold text-gray-900">{{ __('menus.vacancies') }}</h1>
        <a href="{{ route('admin.vacancies.create') }}" class="btn-primary btn">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            {{ __('vacancies.create_vacancy') }}
        </a>
    </div>

    {{-- Filters --}}
    <form method="GET" class="flex flex-wrap gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('messages.search') }}..."
               class="form-input w-full sm:w-56">
        <select name="status" class="form-select w-auto">
            <option value="">{{ __('messages.all_statuses') }}</option>
            @foreach($statuses as $s)
            <option value="{{ $s->value }}" {{ request('status') === $s->value ? 'selected' : '' }}>{{ $s->getLabel() }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn-navy btn">{{ __('messages.filter') }}</button>
        @if(request()->hasAny(['search','status']))
        <a href="{{ route('admin.vacancies.index') }}" class="btn-secondary btn">{{ __('messages.reset') }}</a>
        @endif
    </form>

    {{-- Table --}}
    <div class="overflow-hidden rounded-xl bg-white" style="box-shadow: var(--shadow-card)">
        <table class="min-w-full divide-y divide-gray-100 text-sm">
            <thead class="table-header">
                <tr>
                    <th class="table-th">{{ __('vacancies.code') }}</th>
                    <th class="table-th">{{ __('vacancies.title') }}</th>
                    <th class="hidden table-th sm:table-cell">{{ __('vacancies.department') }}</th>
                    <th class="hidden table-th md:table-cell">{{ __('vacancies.closing_date') }}</th>
                    <th class="table-th">{{ __('vacancies.status') }}</th>
                    <th class="hidden table-th-right sm:table-cell">{{ __('vacancies.applications') }}</th>
                    <th class="table-th-right">{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($vacancies as $vacancy)
                @php
                $statusColors = ['open' => 'badge-green', 'closed' => 'badge-red', 'archived' => 'badge-amber'];
                $statusBadge = $statusColors[$vacancy->status->value] ?? 'badge-gray';
                @endphp
                <tr class="table-row">
                    <td class="table-td font-mono text-xs text-gray-500">{{ $vacancy->code }}</td>
                    <td class="table-td font-medium text-gray-900">{{ $vacancy->title }}</td>
                    <td class="hidden table-td text-gray-500 sm:table-cell">{{ $vacancy->department ?? '—' }}</td>
                    <td class="hidden table-td text-gray-500 md:table-cell">{{ et_date($vacancy->closing_date) }}</td>
                    <td class="table-td">
                        <span class="{{ $statusBadge }}">{{ $vacancy->status->getLabel() }}</span>
                    </td>
                    <td class="hidden table-td text-right text-gray-500 sm:table-cell">{{ $vacancy->applications_count }}</td>
                    <td class="table-td text-right">
                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('admin.vacancies.show', $vacancy) }}"
                               class="text-xs font-medium text-gray-400 hover:text-gray-700 transition">{{ __('messages.view') }}</a>
                            <a href="{{ route('admin.vacancies.edit', $vacancy) }}"
                               class="text-xs font-medium text-accent hover:text-accent-dark transition">{{ __('messages.edit') }}</a>
                            <form method="POST" action="{{ route('admin.vacancies.destroy', $vacancy) }}"
                                  onsubmit="return confirm('{{ __('messages.confirm_delete') }}')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="text-xs font-medium text-red-500 hover:text-red-700 transition">{{ __('messages.delete') }}</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-12 text-center">
                        <div class="flex flex-col items-center gap-2">
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-brand-muted">
                                <svg class="h-6 w-6 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <p class="text-sm text-gray-400">{{ __('messages.no_records') }}</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($vacancies->hasPages())
        <div class="border-t border-gray-100 px-4 py-3">
            {{ $vacancies->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
