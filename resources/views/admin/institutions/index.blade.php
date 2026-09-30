@extends('layouts.admin')
@section('title', __('admin.resource.institutions'))

@section('content')
<div class="space-y-4">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="page-title">{{ __('admin.resource.institutions') }}</h1>
        @can('create', \App\Models\Institution::class)
        <a href="{{ route('admin.institutions.create') }}" class="btn-primary btn">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            {{ __('admin.institution_create') }}
        </a>
        @endcan
    </div>

    <form method="GET" class="filter-bar">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('messages.search') }}..."
               class="form-input w-full sm:w-56">
        <select name="status" class="form-select w-auto">
            <option value="">{{ __('messages.all_statuses') }}</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>{{ __('admin.status_active') }}</option>
            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>{{ __('admin.status_inactive') }}</option>
        </select>
        <button type="submit" class="btn-navy btn">{{ __('messages.filter') }}</button>
        @if(request()->hasAny(['search','status']))
        <a href="{{ route('admin.institutions.index') }}" class="btn-secondary btn">{{ __('messages.reset') }}</a>
        @endif
    </form>

    <div class="card overflow-hidden">
        <table class="min-w-full divide-y divide-gray-100 text-sm">
            <thead class="table-header">
                <tr>
                    <th class="table-th">{{ __('admin.institution_code') }}</th>
                    <th class="table-th">{{ __('admin.institution_name') }}</th>
                    <th class="hidden table-th sm:table-cell">{{ __('admin.institution_type') }}</th>
                    <th class="hidden table-th md:table-cell">{{ __('admin.institution_contact') }}</th>
                    <th class="table-th">{{ __('admin.column.status') }}</th>
                    <th class="hidden table-th-right sm:table-cell">{{ __('vacancies.job_vacancies') }}</th>
                    <th class="table-th-right">{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($institutions as $institution)
                <tr class="table-row">
                    <td class="table-td font-mono text-xs text-gray-500">{{ $institution->code }}</td>
                    <td class="table-td">
                        <div class="font-medium text-gray-900">{{ $institution->name }}</div>
                        @if($institution->short_name)
                        <div class="text-xs text-gray-600">{{ $institution->short_name }}</div>
                        @endif
                    </td>
                    <td class="hidden table-td text-gray-500 sm:table-cell">{{ $institution->type ?? '—' }}</td>
                    <td class="hidden table-td text-gray-500 md:table-cell">
                        @if($institution->email)<div>{{ $institution->email }}</div>@endif
                        @if($institution->phone)<div>{{ $institution->phone }}</div>@endif
                        @if(!$institution->email && !$institution->phone)—@endif
                    </td>
                    <td class="table-td">
                        <span class="{{ $institution->status === 'active' ? 'badge-green' : 'badge-red' }}">
                            {{ $institution->status === 'active' ? __('admin.status_active') : __('admin.status_inactive') }}
                        </span>
                    </td>
                    <td class="hidden table-td text-right text-gray-500 sm:table-cell">{{ $institution->vacancies_count }}</td>
                    <td class="table-td text-right">
                        <div class="flex items-center justify-end gap-3">
                            @can('view', $institution)
                            <a href="{{ route('admin.institutions.show', $institution) }}"
                               class="link-action">{{ __('messages.view') }}</a>
                            @endcan
                            @can('update', $institution)
                            <a href="{{ route('admin.institutions.edit', $institution) }}"
                               class="link-action">{{ __('messages.edit') }}</a>
                            @endcan
                            @if($institution->status === 'active')
                            @can('deactivate', $institution)
                            <form method="POST" action="{{ route('admin.institutions.deactivate', $institution) }}">
                                @csrf
                                <button type="submit" class="link-danger">
                                    {{ __('admin.institution_deactivate') }}
                                </button>
                            </form>
                            @endcan
                            @else
                            @can('activate', $institution)
                            <form method="POST" action="{{ route('admin.institutions.activate', $institution) }}">
                                @csrf
                                <button type="submit" class="link-action">
                                    {{ __('admin.institution_activate') }}
                                </button>
                            </form>
                            @endcan
                            @endif
                            @can('delete', $institution)
                            <form method="POST" action="{{ route('admin.institutions.destroy', $institution) }}"
                                  onsubmit="return confirm('{{ __('messages.confirm_delete') }}')">
                                @csrf @method('DELETE')
                                <button type="submit" class="link-danger">
                                    {{ __('messages.delete') }}
                                </button>
                            </form>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-12 text-center">
                        <p class="text-sm text-gray-600">{{ __('messages.no_records') }}</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($institutions->hasPages())
        <div class="border-t border-gray-100 px-4 py-3">
            {{ $institutions->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
