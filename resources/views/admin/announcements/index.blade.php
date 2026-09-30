@extends('layouts.admin')
@section('title', __('menus.announcements'))

@section('content')
@php
    $stateOf = fn ($ann) => $ann->isPublished() ? 'published' : ($ann->status === 'published' && $ann->published_at?->isFuture() ? 'scheduled' : 'draft');
    $stateBadge = [
        'published' => ['success', __('messages.published')],
        'scheduled' => ['info', __('messages.ann_mode_schedule')],
        'draft'     => ['gray', __('messages.draft')],
    ];
@endphp
<div class="space-y-6">

    <x-admin.page-header :title="__('menus.announcements')"
                         :description="__('messages.ann_list_intro')"
                         :crumbs="[['label' => __('menus.recruitment')], ['label' => __('menus.announcements')]]">
        <a href="{{ route('admin.announcements.create') }}" class="btn btn-primary">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M12 4v16m8-8H4"/></svg>
            {{ __('messages.add_announcement') }}
        </a>
    </x-admin.page-header>

    <x-admin.filters :reset="route('admin.announcements.index')" :active="request()->hasAny(['search', 'state'])">
        <div class="lg:col-span-2">
            <label for="search" class="form-label">{{ __('messages.search') }}</label>
            <input type="search" id="search" name="search" value="{{ request('search') }}" placeholder="{{ __('messages.ann_search_placeholder') }}" class="form-input">
        </div>
        <div>
            <label for="state" class="form-label">{{ __('vacancies.status') }}</label>
            <select id="state" name="state" class="form-select">
                <option value="">{{ __('messages.all_statuses') }}</option>
                @foreach($stateBadge as $value => [, $label])
                <option value="{{ $value }}" @selected(request('state') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </x-admin.filters>

    <x-admin.table-card :meta="trans_choice('messages.records_count', $announcements->total(), ['count' => number_format($announcements->total())])">
        <table class="min-w-full divide-y divide-gray-100 text-sm">
            <thead class="table-header">
                <tr>
                    <th class="table-th">{{ __('messages.ann_title') }}</th>
                    <th class="table-th hidden md:table-cell">{{ __('messages.ann_period') }}</th>
                    <th class="table-th-right hidden sm:table-cell">{{ __('menus.vacancies') }}</th>
                    <th class="table-th">{{ __('vacancies.status') }}</th>
                    <th class="table-th-right"><span class="sr-only">{{ __('messages.actions') }}</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($announcements as $ann)
                @php [$tone, $label] = $stateBadge[$stateOf($ann)]; @endphp
                <tr class="table-row">
                    <td class="table-td">
                        <a href="{{ route('admin.announcements.show', $ann) }}" class="font-semibold text-gray-900 hover:text-brand">{{ $ann->subject }}</a>
                        <p class="text-xs text-gray-600"><span class="font-mono">{{ $ann->code }}</span> · {{ $ann->author?->name ?? '—' }}</p>
                    </td>
                    <td class="table-td table-td-muted hidden md:table-cell">
                        @if($ann->opening_date && $ann->closing_date){{ et_date($ann->opening_date, 'M d') }} – {{ et_date($ann->closing_date, 'M d, Y') }}@else — @endif
                    </td>
                    <td class="table-td hidden text-right tabular-nums sm:table-cell">{{ $ann->vacancies_count }}</td>
                    <td class="table-td">
                        <x-admin.status :tone="$tone" :label="$label" />
                        @if($stateOf($ann) === 'scheduled')<p class="mt-0.5 text-xs text-gray-600">{{ et_date($ann->published_at) }}</p>@endif
                    </td>
                    <td class="table-td">
                        <div class="table-actions">
                            <a href="{{ route('admin.announcements.edit', $ann) }}" class="btn btn-secondary btn-sm">{{ __('messages.edit') }}</a>
                            <x-admin.row-menu>
                                <a href="{{ route('admin.announcements.show', $ann) }}" class="menu-item">{{ __('messages.view') }}</a>
                                @if($ann->isPublished())
                                <a href="{{ route('announcements.show', $ann) }}" target="_blank" rel="noopener" class="menu-item">{{ __('messages.ann_view_public') }}</a>
                                @endif
                                @can('vacancies.create')
                                <a href="{{ route('admin.vacancies.create', ['announcement_id' => $ann->id]) }}" class="menu-item">{{ __('vacancies.create_vacancy') }}</a>
                                @endcan
                                <div class="menu-divider"></div>
                                <form method="POST" action="{{ route('admin.announcements.destroy', $ann) }}" onsubmit="return confirm(@js(__('messages.confirm_delete')))">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="menu-item menu-item-danger">{{ __('messages.delete') }}</button>
                                </form>
                            </x-admin.row-menu>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5">
                    <x-admin.empty :text="request()->hasAny(['search', 'state']) ? __('messages.empty_filtered') : __('messages.ann_empty_hint')">
                        <a href="{{ route('admin.announcements.create') }}" class="btn btn-primary btn-sm">{{ __('messages.add_announcement') }}</a>
                    </x-admin.empty>
                </td></tr>
                @endforelse
            </tbody>
        </table>
        <x-slot:footer>{{ $announcements->links() }}</x-slot:footer>
    </x-admin.table-card>
</div>
@endsection
