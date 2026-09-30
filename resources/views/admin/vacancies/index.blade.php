@extends('layouts.admin')
@section('title', __('menus.vacancies'))

@section('content')
<div class="space-y-5">

    <x-admin.page-header :title="__('menus.vacancies')"
                         :description="trans_choice('messages.records_count', $vacancies->total(), ['count' => number_format($vacancies->total())])">
        <a href="{{ route('admin.vacancies.create') }}" class="btn btn-primary">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
            {{ __('vacancies.create_vacancy') }}
        </a>
    </x-admin.page-header>

    <form method="GET" class="filter-bar" role="search">
        <div class="filter-field-lg">
            <label for="f-search" class="form-label">{{ __('messages.search') }}</label>
            <input type="search" id="f-search" name="search" value="{{ request('search') }}"
                   placeholder="{{ __('messages.search_vacancies_placeholder') }}" class="form-input">
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
        <div class="filter-field">
            <label for="f-institution" class="form-label">{{ __('admin.institution_name') }}</label>
            <select id="f-institution" name="institution_id" class="form-select">
                <option value="">{{ __('admin.all_institutions') }}</option>
                @foreach($institutions as $inst)
                <option value="{{ $inst->id }}" @selected(request('institution_id') === $inst->id)>{{ $inst->short_name ?? $inst->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter-actions">
            <button type="submit" class="btn btn-primary">{{ __('messages.filter') }}</button>
            @if(request()->hasAny(['search','status','institution_id']))
            <a href="{{ route('admin.vacancies.index') }}" class="btn btn-ghost">{{ __('messages.reset') }}</a>
            @endif
        </div>
    </form>

    <div class="table-wrap">
        <div class="table-scroll">
            <table class="min-w-full">
                <thead class="table-header">
                    <tr>
                        <th class="table-th">{{ __('vacancies.title') }}</th>
                        <th class="hidden table-th lg:table-cell">{{ __('admin.institution_name') }}</th>
                        <th class="hidden table-th sm:table-cell">{{ __('vacancies.department') }}</th>
                        <th class="hidden table-th md:table-cell">{{ __('vacancies.closing_date') }}</th>
                        <th class="table-th">{{ __('vacancies.status') }}</th>
                        <th class="hidden table-th-right sm:table-cell">{{ __('vacancies.applications') }}</th>
                        <th class="table-th-right">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($vacancies as $vacancy)
                    <tr class="table-row">
                        <td class="table-td">
                            <a href="{{ route('admin.vacancies.show', $vacancy) }}" class="font-semibold text-gray-900 hover:text-brand hover:underline">{{ $vacancy->title }}</a>
                            <p class="font-mono text-[13px] text-gray-600">{{ $vacancy->code }}</p>
                        </td>
                        <td class="hidden table-td table-td-muted lg:table-cell">{{ $vacancy->institution?->displayName() ?? '—' }}</td>
                        <td class="hidden table-td table-td-muted sm:table-cell">{{ $vacancy->department ?? '—' }}</td>
                        <td class="hidden table-td table-td-muted tabular-nums md:table-cell">
                            {{ $vacancy->announcement?->closing_date ? et_date($vacancy->announcement->closing_date) : '—' }}
                        </td>
                        <td class="table-td"><x-admin.status :status="$vacancy->status" /></td>
                        <td class="hidden table-td text-right tabular-nums sm:table-cell">{{ number_format($vacancy->applications_count) }}</td>
                        <td class="table-td">
                            <div class="table-actions">
                                <a href="{{ route('admin.vacancies.edit', $vacancy) }}" class="btn btn-secondary btn-sm">{{ __('messages.edit') }}</a>
                                <form method="POST" action="{{ route('admin.vacancies.destroy', $vacancy) }}"
                                      onsubmit="return confirm(@js(__('messages.confirm_delete')))">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger-soft btn-sm">{{ __('messages.delete') }}</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7">
                            <x-admin.empty :text="__('messages.empty_vacancies_hint')">
                                <a href="{{ route('admin.vacancies.create') }}" class="btn btn-primary">{{ __('vacancies.create_vacancy') }}</a>
                            </x-admin.empty>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($vacancies->hasPages())
        <div class="table-footer">{{ $vacancies->links() }}</div>
        @endif
    </div>
</div>
@endsection
