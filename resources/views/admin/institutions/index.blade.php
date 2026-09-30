@extends('layouts.admin')
@section('title', __('admin.resource.institutions'))

@section('content')
<div class="space-y-6">

    <x-admin.page-header :title="__('admin.resource.institutions')"
                         :description="__('messages.inst_intro')"
                         :crumbs="[['label' => __('menus.recruitment')], ['label' => __('admin.resource.institutions')]]">
        @can('create', \App\Models\Institution::class)
        <a href="{{ route('admin.institutions.create') }}" class="btn btn-primary">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M12 4v16m8-8H4"/></svg>
            {{ __('admin.institution_create') }}
        </a>
        @endcan
    </x-admin.page-header>

    <x-admin.filters :reset="route('admin.institutions.index')" :active="request()->hasAny(['search', 'status'])">
        <div class="lg:col-span-2">
            <label for="search" class="form-label">{{ __('messages.search') }}</label>
            <input type="search" id="search" name="search" value="{{ request('search') }}" placeholder="{{ __('messages.inst_search_placeholder') }}" class="form-input">
        </div>
        <div>
            <label for="status" class="form-label">{{ __('admin.column.status') }}</label>
            <select id="status" name="status" class="form-select">
                <option value="">{{ __('messages.all_statuses') }}</option>
                <option value="active" @selected(request('status') === 'active')>{{ __('admin.status_active') }}</option>
                <option value="inactive" @selected(request('status') === 'inactive')>{{ __('admin.status_inactive') }}</option>
            </select>
        </div>
    </x-admin.filters>

    <x-admin.table-card :meta="trans_choice('messages.records_count', $institutions->total(), ['count' => number_format($institutions->total())])">
        <table class="min-w-full divide-y divide-gray-100 text-sm">
            <thead class="table-header">
                <tr>
                    <th class="table-th">{{ __('admin.institution_name') }}</th>
                    <th class="table-th hidden sm:table-cell">{{ __('admin.institution_type') }}</th>
                    <th class="table-th hidden md:table-cell">{{ __('admin.institution_contact') }}</th>
                    <th class="table-th">{{ __('admin.column.status') }}</th>
                    <th class="table-th-right hidden sm:table-cell">{{ __('vacancies.job_vacancies') }}</th>
                    <th class="table-th-right"><span class="sr-only">{{ __('messages.actions') }}</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($institutions as $institution)
                <tr class="table-row">
                    <td class="table-td">
                        <a href="{{ route('admin.institutions.show', $institution) }}" class="font-semibold text-gray-900 hover:text-brand">{{ $institution->name }}</a>
                        <p class="text-xs text-gray-600"><span class="font-mono">{{ $institution->code }}</span>@if($institution->short_name) · {{ $institution->short_name }}@endif</p>
                    </td>
                    <td class="table-td table-td-muted hidden sm:table-cell">{{ $institution->type ?: '—' }}</td>
                    <td class="table-td table-td-muted hidden md:table-cell">
                        @if($institution->email)<div class="truncate">{{ $institution->email }}</div>@endif
                        @if($institution->phone)<div>{{ $institution->phone }}</div>@endif
                        @if(! $institution->email && ! $institution->phone)—@endif
                    </td>
                    <td class="table-td">
                        <x-admin.status :tone="$institution->status === 'active' ? 'success' : 'gray'"
                                        :label="$institution->status === 'active' ? __('admin.status_active') : __('admin.status_inactive')" />
                    </td>
                    <td class="table-td hidden text-right tabular-nums sm:table-cell">{{ $institution->vacancies_count }}</td>
                    <td class="table-td">
                        <div class="table-actions">
                            @can('view', $institution)
                            <a href="{{ route('admin.institutions.show', $institution) }}" class="btn btn-secondary btn-sm">{{ __('messages.view') }}</a>
                            @endcan
                            <x-admin.row-menu>
                                @can('update', $institution)
                                <a href="{{ route('admin.institutions.edit', $institution) }}" class="menu-item">{{ __('messages.edit') }}</a>
                                @endcan
                                @if($institution->status === 'active')
                                    @can('deactivate', $institution)
                                    <form method="POST" action="{{ route('admin.institutions.deactivate', $institution) }}">@csrf
                                        <button type="submit" class="menu-item">{{ __('admin.institution_deactivate') }}</button>
                                    </form>
                                    @endcan
                                @else
                                    @can('activate', $institution)
                                    <form method="POST" action="{{ route('admin.institutions.activate', $institution) }}">@csrf
                                        <button type="submit" class="menu-item">{{ __('admin.institution_activate') }}</button>
                                    </form>
                                    @endcan
                                @endif
                                @can('delete', $institution)
                                <div class="menu-divider"></div>
                                <form method="POST" action="{{ route('admin.institutions.destroy', $institution) }}" onsubmit="return confirm(@js(__('messages.confirm_delete')))">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="menu-item menu-item-danger">{{ __('messages.delete') }}</button>
                                </form>
                                @endcan
                            </x-admin.row-menu>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6">
                    <x-admin.empty :text="request()->hasAny(['search', 'status']) ? __('messages.empty_filtered') : __('messages.inst_empty_hint')">
                        @can('create', \App\Models\Institution::class)
                        <a href="{{ route('admin.institutions.create') }}" class="btn btn-primary btn-sm">{{ __('admin.institution_create') }}</a>
                        @endcan
                    </x-admin.empty>
                </td></tr>
                @endforelse
            </tbody>
        </table>
        <x-slot:footer>{{ $institutions->links() }}</x-slot:footer>
    </x-admin.table-card>
</div>
@endsection
