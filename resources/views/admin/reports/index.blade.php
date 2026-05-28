@extends('layouts.admin')
@section('title', __('menus.reports'))
@section('content')
<div class="space-y-5">
    <div>
        <h1 class="text-lg font-semibold text-gray-900">{{ __('admin.reports_center.title') }}</h1>
        <p class="text-sm text-gray-500">{{ __('menus.reports') }}</p>
    </div>

    <form method="GET" class="flex flex-wrap gap-2">
        <select name="vacancy_id" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand focus:ring-1 focus:ring-brand">
            <option value="">{{ __('messages.all_vacancies') }}</option>
            @foreach($vacancies as $v)
            <option value="{{ $v->id }}" {{ request('vacancy_id') == $v->id ? 'selected' : '' }}>{{ $v->code }} — {{ $v->title }}</option>
            @endforeach
        </select>
        <select name="status" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand focus:ring-1 focus:ring-brand">
            <option value="">{{ __('messages.all_statuses') }}</option>
            @foreach($statuses as $s)
            <option value="{{ $s->value }}" {{ request('status') === $s->value ? 'selected' : '' }}>{{ $s->getLabel() }}</option>
            @endforeach
        </select>
        @if(app()->getLocale() === 'am')
            <x-ethiopian-datepicker name="date_from" :label="__('messages.date_from')" :value="request('date_from')"/>
            <x-ethiopian-datepicker name="date_until" :label="__('messages.date_until')" :value="request('date_until')"/>
        @else
        <input type="date" name="date_from" value="{{ request('date_from') }}"
               class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand focus:ring-1 focus:ring-brand">
        <input type="date" name="date_until" value="{{ request('date_until') }}"
               class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand focus:ring-1 focus:ring-brand">
        @endif
        <button type="submit" class="btn btn-navy">{{ __('messages.filter') }}</button>
        @if(request()->hasAny(['vacancy_id','status','date_from','date_until']))
        <a href="{{ route('admin.reports.index') }}" class="btn btn-secondary">{{ __('messages.reset') }}</a>
        @endif
    </form>

    <div class="grid gap-4 sm:grid-cols-3">
        @php
        $kpiDefs = [
            'total'            => ['label' => __('dashboard.kpi.total_applications'), 'bar' => 'bg-brand'],
            'passed_screening' => ['label' => __('dashboard.kpi.passed_screening'),   'bar' => 'bg-green-500'],
            'failed_screening' => ['label' => __('dashboard.kpi.failed_screening'),   'bar' => 'bg-red-500'],
        ];
        @endphp
        @foreach($kpiDefs as $key => $card)
        <div class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-card">
            <div class="h-1 {{ $card['bar'] }}"></div>
            <div class="p-5">
                <p class="text-sm text-gray-500">{{ $card['label'] }}</p>
                <p class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($summary[$key]) }}</p>
            </div>
        </div>
        @endforeach
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-100 text-sm">
            <thead class="table-header">
                <tr>
                    <th class="table-th">{{ __('messages.applicant') }}</th>
                    <th class="table-th hidden sm:table-cell">{{ __('menus.vacancies') }}</th>
                    <th class="table-th hidden md:table-cell">{{ __('messages.reference') }}</th>
                    <th class="table-th">{{ __('vacancies.status') }}</th>
                    <th class="table-th hidden lg:table-cell">{{ __('messages.submitted') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($applications as $app)
                @php
                $badgeMap = ['submitted'=>'badge-blue','passed_screening'=>'badge-green','failed_screening'=>'badge-red','draft'=>'badge-gray'];
                $badgeClass = $badgeMap[$app->status->value] ?? 'badge-gray';
                @endphp
                <tr class="table-row">
                    <td class="table-td font-medium text-gray-900">{{ $app->applicant?->full_name }}</td>
                    <td class="table-td hidden text-gray-600 sm:table-cell">{{ $app->vacancy?->title }}</td>
                    <td class="table-td hidden font-mono text-xs text-gray-400 md:table-cell">{{ $app->reference_number }}</td>
                    <td class="table-td"><span class="{{ $badgeClass }}">{{ $app->status->getLabel() }}</span></td>
                    <td class="table-td hidden text-gray-500 lg:table-cell">{{ et_date($app->created_at) }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-10 text-center text-gray-400">{{ __('messages.no_records') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($applications->hasPages())
        <div class="border-t border-gray-100 px-4 py-3">{{ $applications->links() }}</div>
        @endif
    </div>
</div>
@endsection
