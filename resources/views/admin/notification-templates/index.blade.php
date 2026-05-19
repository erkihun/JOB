@extends('layouts.admin')
@section('title', __('menus.notification_templates'))
@section('content')
<div class="space-y-5">
    <h1 class="text-lg font-semibold text-gray-900">{{ __('menus.notification_templates') }}</h1>
    <div class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-100 text-sm">
            <thead class="table-header">
                <tr>
                    <th class="table-th">{{ __('dashboard.table.type') }}</th>
                    <th class="table-th">{{ __('messages.locale') }}</th>
                    <th class="table-th">{{ __('messages.subject') }}</th>
                    <th class="table-th">{{ __('vacancies.status') }}</th>
                    <th class="table-th-right">{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($templates as $template)
                <tr class="table-row">
                    <td class="table-td">
                        <span class="rounded-full bg-brand-muted px-2 py-0.5 text-xs font-medium text-brand">{{ $template->type->getLabel() }}</span>
                    </td>
                    <td class="table-td font-mono text-xs uppercase text-gray-500">{{ $template->locale }}</td>
                    <td class="table-td font-medium text-gray-800">{{ $template->subject }}</td>
                    <td class="table-td">
                        @php $statusBadge = $template->active ? 'badge-green' : 'badge-gray'; @endphp
                        <span class="{{ $statusBadge }}">
                            {{ $template->active ? __('messages.active') : __('messages.inactive') }}
                        </span>
                    </td>
                    <td class="table-td text-right">
                        <a href="{{ route('admin.notification-templates.edit', $template) }}" class="text-xs font-medium text-brand hover:text-brand-dark">{{ __('messages.edit') }}</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-10 text-center text-gray-400">{{ __('messages.no_records') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
