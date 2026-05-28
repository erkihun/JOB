@extends('layouts.admin')
@section('title', __('menus.applications'))

@section('content')
<div class="space-y-5">
    <h1 class="text-lg font-semibold text-gray-900">{{ __('menus.applications') }}</h1>

    <form method="GET" class="flex flex-wrap gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('messages.search') }}..."
               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand focus:ring-1 focus:ring-brand sm:w-56">
        <select name="vacancy_id" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand focus:ring-1 focus:ring-brand">
            <option value="">{{ __('messages.all_vacancies') }}</option>
            @foreach($vacancies as $v)
            <option value="{{ $v->id }}" {{ request('vacancy_id') === $v->id ? 'selected' : '' }}>{{ $v->code }} — {{ $v->title }}</option>
            @endforeach
        </select>
        <select name="status" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand focus:ring-1 focus:ring-brand">
            <option value="">{{ __('messages.all_statuses') }}</option>
            @foreach($statuses as $s)
            <option value="{{ $s->value }}" {{ request('status') === $s->value ? 'selected' : '' }}>{{ $s->getLabel() }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-navy">{{ __('messages.filter') }}</button>
        @if(request()->hasAny(['search','vacancy_id','status']))
        <a href="{{ route('admin.applications.index') }}" class="btn btn-secondary">{{ __('messages.reset') }}</a>
        @endif
    </form>

    <div class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-100 text-sm">
            <thead class="table-header">
                <tr>
                    <th class="table-th">{{ __('messages.applicant') }}</th>
                    <th class="table-th hidden sm:table-cell">{{ __('menus.vacancies') }}</th>
                    <th class="table-th hidden xl:table-cell">{{ __('admin.institution_name') }}</th>
                    <th class="table-th hidden md:table-cell">{{ __('messages.reference') }}</th>
                    <th class="table-th">{{ __('vacancies.status') }}</th>
                    <th class="table-th hidden lg:table-cell">{{ __('messages.submitted') }}</th>
                    <th class="table-th-right">{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($applications as $app)
                @php
                $badgeMap = ['submitted'=>'badge-blue','passed_screening'=>'badge-green','failed_screening'=>'badge-red','draft'=>'badge-gray'];
                $badgeClass = $badgeMap[$app->status->value] ?? 'badge-gray';
                @endphp
                <tr class="table-row">
                    <td class="table-td">
                        <p class="font-medium text-gray-900">{{ $app->applicant?->full_name }}</p>
                        <p class="text-xs text-gray-400">{{ $canViewSensitive ? ($app->applicant?->phone ?? '—') : __('dashboard.restricted') }}</p>
                    </td>
                    <td class="table-td hidden sm:table-cell">
                        <p class="font-medium text-gray-800">{{ $app->vacancy?->title }}</p>
                        <p class="text-xs text-gray-400">{{ $app->vacancy?->code }}</p>
                    </td>
                    <td class="table-td hidden xl:table-cell text-gray-500 text-xs">
                        {{ $app->vacancy?->institution?->displayName() ?? '—' }}
                    </td>
                    <td class="table-td hidden font-mono text-xs text-gray-500 md:table-cell">{{ $app->reference_number }}</td>
                    <td class="table-td"><span class="{{ $badgeClass }}">{{ $app->status->getLabel() }}</span></td>
                    <td class="table-td hidden text-gray-500 lg:table-cell">{{ et_date($app->created_at) }}</td>
                    <td class="table-td text-right">
                        <a href="{{ route('admin.applications.show', $app) }}" class="text-xs font-medium text-brand hover:text-brand-dark">{{ __('messages.view') }}</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-10 text-center text-gray-400">{{ __('messages.no_records') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($applications->hasPages())
        <div class="border-t border-gray-100 px-4 py-3">{{ $applications->links() }}</div>
        @endif
    </div>
</div>
@endsection
