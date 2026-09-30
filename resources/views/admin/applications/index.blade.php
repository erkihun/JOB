@extends('layouts.admin')
@section('title', __('menus.applications'))

@section('content')
<div class="space-y-5">
    <x-admin.page-header :title="__('menus.applications')"
                         :description="trans_choice('messages.records_count', $applications->total(), ['count' => number_format($applications->total())])" />

    <form method="GET" class="filter-bar" role="search">
        <div class="filter-field-lg">
            <label for="f-search" class="form-label">{{ __('messages.search') }}</label>
            <input type="search" id="f-search" name="search" value="{{ request('search') }}"
                   placeholder="{{ __('messages.search_applicants_placeholder') }}" class="form-input">
        </div>
        <div class="filter-field">
            <label for="f-institution" class="form-label">{{ __('admin.institution_name') }}</label>
            <select id="f-institution" name="institution_id" class="form-select">
                <option value="">{{ __('applications.filter_by_institution') }}</option>
                @foreach($institutions as $inst)
                <option value="{{ $inst->id }}" @selected(request('institution_id') === $inst->id)>{{ $inst->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter-field">
            <label for="f-vacancy" class="form-label">{{ __('menus.vacancies') }}</label>
            <select id="f-vacancy" name="vacancy_id" class="form-select">
                <option value="">{{ __('messages.all_vacancies') }}</option>
                @foreach($vacancies as $v)
                <option value="{{ $v->id }}" @selected(request('vacancy_id') === $v->id)>{{ $v->code }} — {{ $v->title }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter-field">
            <label for="f-status" class="form-label">{{ __('vacancies.status') }}</label>
            <select id="f-status" name="status" class="form-select">
                <option value="">{{ __('messages.all_statuses') }}</option>
                @foreach($statuses as $s)
                <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->getLabel() }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter-actions">
            <button type="submit" class="btn btn-primary">{{ __('messages.filter') }}</button>
            @if(request()->hasAny(['search','vacancy_id','institution_id','status']))
            <a href="{{ route('admin.applications.index') }}" class="btn btn-ghost">{{ __('messages.reset') }}</a>
            @endif
        </div>
    </form>

    <div class="table-wrap">
        <div class="table-scroll">
            <table class="min-w-full">
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
                    <tr class="table-row">
                        <td class="table-td">
                            <p class="font-semibold">{{ $app->applicant?->full_name }}</p>
                            <p class="text-[13px] text-gray-600">{{ $canViewSensitive ? ($app->applicant?->phone ?? '—') : __('dashboard.restricted') }}</p>
                        </td>
                        <td class="table-td hidden sm:table-cell">
                            <p>{{ $app->vacancy?->title }}</p>
                            <p class="font-mono text-[13px] text-gray-600">{{ $app->vacancy?->code }}</p>
                        </td>
                        <td class="table-td table-td-muted hidden xl:table-cell">
                            {{ $app->vacancy?->institution?->displayName() ?? '—' }}
                        </td>
                        <td class="table-td hidden font-mono text-[13px] text-gray-700 md:table-cell">{{ $app->reference_number }}</td>
                        <td class="table-td"><x-admin.status :status="$app->status" /></td>
                        <td class="table-td table-td-muted hidden tabular-nums lg:table-cell">{{ et_date($app->created_at) }}</td>
                        <td class="table-td text-right">
                            <a href="{{ route('admin.applications.show', $app) }}" class="btn btn-secondary btn-sm">{{ __('messages.view') }}</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7"><x-admin.empty /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($applications->hasPages())
        <div class="table-footer">{{ $applications->links() }}</div>
        @endif
    </div>
</div>
@endsection
