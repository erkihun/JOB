@extends('layouts.admin')
@section('title', __('menus.audit_logs'))
@section('content')
@php
    $readable = fn (?string $v) => \Illuminate\Support\Str::headline((string) $v);
    $filtersActive = request()->hasAny(['search', 'module', 'action', 'date_from', 'date_until']);
    $tone = fn (string $action) => match (true) {
        str_contains($action, 'delete') || str_contains($action, 'failed') || str_contains($action, 'reset') => 'danger',
        str_contains($action, 'created') || str_contains($action, 'passed') || str_contains($action, 'exported') => 'success',
        str_contains($action, 'login') || str_contains($action, 'password') => 'warning',
        default => 'info',
    };
@endphp
<div class="space-y-6">
    <x-admin.page-header :title="__('menus.audit_logs')"
                         :description="__('messages.audit_intro')"
                         :crumbs="[['label' => __('menus.system')], ['label' => __('menus.audit_logs')]]">
        @if(auth()->user()?->can('reports.export') && auth()->user()?->can('reports.audit'))
        <a href="{{ route('admin.reports.export', ['report' => 'audit-log']) }}" class="btn btn-secondary" data-admin-no-spa>{{ __('messages.report_download_excel') }}</a>
        @endif
    </x-admin.page-header>

    <x-admin.filters :reset="route('admin.audit-logs.index')" :active="$filtersActive">
        <div>
            <label for="search" class="form-label">{{ __('messages.performed_by') }}</label>
            <input type="search" id="search" name="search" value="{{ request('search') }}" placeholder="{{ __('messages.audit_user_placeholder') }}" class="form-input">
        </div>
        <div>
            <label for="module" class="form-label">{{ __('dashboard.table.module') }}</label>
            <select id="module" name="module" class="form-select">
                <option value="">{{ __('messages.all_modules') }}</option>
                @foreach($modules as $m)
                <option value="{{ $m }}" @selected(request('module') === $m)>{{ $readable($m) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="action" class="form-label">{{ __('dashboard.table.action') }}</label>
            <select id="action" name="action" class="form-select">
                <option value="">{{ __('messages.all_actions') }}</option>
                @foreach($actions as $a)
                <option value="{{ $a }}" @selected(request('action') === $a)>{{ $readable($a) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="date_from" class="form-label">{{ __('messages.date_from') }}</label>
            <input type="date" id="date_from" name="date_from" value="{{ request('date_from') }}" class="form-input">
        </div>
        <div>
            <label for="date_until" class="form-label">{{ __('messages.date_until') }}</label>
            <input type="date" id="date_until" name="date_until" value="{{ request('date_until') }}" class="form-input">
        </div>
    </x-admin.filters>

    <x-admin.table-card :meta="trans_choice('messages.records_count', $logs->total(), ['count' => number_format($logs->total())])">
        <table class="min-w-full divide-y divide-gray-100 text-sm">
            <thead class="table-header">
                <tr>
                    <th class="table-th">{{ __('dashboard.table.time_ago') }}</th>
                    <th class="table-th">{{ __('messages.performed_by') }}</th>
                    <th class="table-th">{{ __('dashboard.table.action') }}</th>
                    <th class="table-th hidden md:table-cell">{{ __('dashboard.table.module') }}</th>
                    <th class="table-th hidden lg:table-cell">{{ __('messages.audit_ip') }}</th>
                    <th class="table-th-right"><span class="sr-only">{{ __('messages.audit_details') }}</span></th>
                </tr>
            </thead>
            @forelse($logs as $log)
            @php $hasDetails = ! empty($log->old_values) || ! empty($log->new_values); @endphp
            <tbody class="divide-y divide-gray-100 border-t border-gray-100" x-data="{ open: false }">
                <tr class="table-row">
                    <td class="table-td whitespace-nowrap">
                        <p class="text-gray-900">{{ et_date($log->created_at) }} <span class="text-gray-600">{{ $log->created_at->format('H:i') }}</span></p>
                        <p class="text-xs text-gray-500">{{ et_diff_for_humans($log->created_at) }}</p>
                    </td>
                    <td class="table-td font-semibold text-gray-900">{{ $log->user?->name ?? __('dashboard.system') }}</td>
                    <td class="table-td"><x-admin.status :tone="$tone($log->action)" :label="$readable($log->action)" /></td>
                    <td class="table-td table-td-muted hidden md:table-cell">{{ $readable($log->module) }}</td>
                    <td class="table-td hidden font-mono text-xs text-gray-600 lg:table-cell">{{ $log->ip_address }}</td>
                    <td class="table-td">
                        <div class="table-actions">
                            @if($hasDetails)
                            <button type="button" @click="open = !open" :aria-expanded="open.toString()" class="btn btn-ghost btn-sm">
                                <span x-text="open ? @js(__('messages.audit_hide')) : @js(__('messages.audit_details'))">{{ __('messages.audit_details') }}</span>
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @if($hasDetails)
                <tr x-show="open" x-cloak class="bg-gray-50">
                    <td colspan="6" class="px-4 py-3">
                        <div class="grid gap-3 md:grid-cols-2">
                            @foreach(['old_values' => __('messages.audit_before'), 'new_values' => __('messages.audit_after')] as $field => $heading)
                            @if(! empty($log->{$field}))
                            <div>
                                <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-600">{{ $heading }}</p>
                                <pre class="max-h-60 overflow-auto rounded-lg border border-gray-200 bg-white p-3 text-xs text-gray-800">{{ json_encode($log->{$field}, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                            </div>
                            @endif
                            @endforeach
                        </div>
                    </td>
                </tr>
                @endif
            </tbody>
            @empty
            <tbody><tr><td colspan="6"><x-admin.empty :text="$filtersActive ? __('messages.empty_filtered') : null" /></td></tr></tbody>
            @endforelse
        </table>
        <x-slot:footer>{{ $logs->links() }}</x-slot:footer>
    </x-admin.table-card>
</div>
@endsection
