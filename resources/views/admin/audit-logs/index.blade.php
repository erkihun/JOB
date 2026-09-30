@extends('layouts.admin')
@section('title', __('menus.audit_logs'))
@section('content')
<div class="space-y-5">
    <h1 class="page-title">{{ __('menus.audit_logs') }}</h1>

    <form method="GET" class="filter-bar">
        <select name="module" class="form-select">
            <option value="">{{ __('messages.all_modules') }}</option>
            @foreach($modules as $m)
            <option value="{{ $m }}" {{ request('module') === $m ? 'selected' : '' }}>{{ $m }}</option>
            @endforeach
        </select>
        <select name="action" class="form-select">
            <option value="">{{ __('messages.all_actions') }}</option>
            @foreach($actions as $a)
            <option value="{{ $a }}" {{ request('action') === $a ? 'selected' : '' }}>{{ $a }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-navy">{{ __('messages.filter') }}</button>
        @if(request()->hasAny(['module','action']))
        <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-secondary">{{ __('messages.reset') }}</a>
        @endif
    </form>

    <div class="card overflow-hidden">
        <table class="min-w-full divide-y divide-gray-100 text-sm">
            <thead class="table-header">
                <tr>
                    <th class="table-th">{{ __('messages.performed_by') }}</th>
                    <th class="table-th">{{ __('dashboard.table.action') }}</th>
                    <th class="table-th hidden sm:table-cell">{{ __('dashboard.table.module') }}</th>
                    <th class="table-th hidden md:table-cell">IP</th>
                    <th class="table-th">{{ __('dashboard.table.time_ago') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($logs as $log)
                <tr class="table-row">
                    <td class="table-td font-medium text-gray-900">{{ $log->user?->name ?? __('dashboard.system') }}</td>
                    <td class="table-td">
                        <span class="rounded-full bg-brand-muted px-2 py-0.5 text-xs font-medium text-brand">{{ $log->action }}</span>
                    </td>
                    <td class="table-td hidden text-gray-600 sm:table-cell">{{ $log->module }}</td>
                    <td class="table-td hidden font-mono text-xs text-gray-600 md:table-cell">{{ $log->ip_address }}</td>
                    <td class="table-td text-xs text-gray-600">{{ $log->created_at->diffForHumans() }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-10 text-center text-gray-600">{{ __('messages.no_records') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($logs->hasPages())
        <div class="border-t border-gray-100 px-4 py-3">{{ $logs->links() }}</div>
        @endif
    </div>
</div>
@endsection
