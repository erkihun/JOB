@extends('layouts.admin')
@section('title', __('menus.applicants'))

@section('content')
@php $restricted = __('dashboard.restricted'); @endphp
<div class="space-y-6">

    <x-admin.page-header :title="__('menus.applicants')"
                         :description="__('messages.applicants_intro')"
                         :crumbs="[['label' => __('menus.recruitment')], ['label' => __('menus.applicants')]]" />

    <x-admin.filters :action="route('admin.applicants.index')" :reset="route('admin.applicants.index')" :active="filled($search)">
        <div class="lg:col-span-3">
            <label for="search" class="form-label">{{ __('messages.search') }}</label>
            <input type="search" id="search" name="search" value="{{ $search }}" placeholder="{{ __('messages.search_applicants') }}" class="form-input">
        </div>
    </x-admin.filters>

    <x-admin.table-card :meta="trans_choice('messages.records_count', $applicants->total(), ['count' => number_format($applicants->total())])">
        <table class="min-w-full divide-y divide-gray-100 text-sm">
            <thead class="table-header">
                <tr>
                    <th class="table-th">{{ __('fields.full_name') }}</th>
                    <th class="table-th hidden md:table-cell">{{ __('fields.phone') }}</th>
                    <th class="table-th hidden lg:table-cell">{{ __('fields.gender') }}</th>
                    <th class="table-th-right">{{ __('menus.applications') }}</th>
                    <th class="table-th hidden sm:table-cell">{{ __('fields.registered_at') }}</th>
                    <th class="table-th-right"><span class="sr-only">{{ __('messages.actions') }}</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($applicants as $applicant)
                <tr class="table-row">
                    <td class="table-td">
                        <div class="flex items-center gap-3">
                            <x-admin.avatar :name="$applicant->full_name" :photo="$applicant->profile_photo_path ? route('admin.applicants.photo', $applicant) : null" />
                            <div class="min-w-0">
                                <a href="{{ route('admin.applicants.show', $applicant) }}" class="block truncate font-semibold text-gray-900 hover:text-brand">{{ $applicant->full_name ?: '—' }}</a>
                                <p class="truncate text-[13px] text-gray-600">
                                    @if($applicant->applicant_code)<span class="font-mono">{{ $applicant->applicant_code }}</span> · @endif{{ $canViewSensitive ? $applicant->email : $restricted }}
                                </p>
                            </div>
                        </div>
                    </td>
                    <td class="table-td table-td-muted hidden md:table-cell">{{ $canViewSensitive ? ($applicant->phone ?: '—') : $restricted }}</td>
                    <td class="table-td table-td-muted hidden lg:table-cell">{{ $applicant->gender?->label() ?? '—' }}</td>
                    <td class="table-td text-right"><span class="badge badge-blue tabular-nums">{{ $applicant->applications_count }}</span></td>
                    <td class="table-td table-td-muted hidden sm:table-cell">{{ et_date($applicant->created_at) }}</td>
                    <td class="table-td"><div class="table-actions"><a href="{{ route('admin.applicants.show', $applicant) }}" class="btn btn-secondary btn-sm">{{ __('messages.view') }}</a></div></td>
                </tr>
                @empty
                <tr><td colspan="6"><x-admin.empty :title="__('messages.no_applicants_found')" :text="filled($search) ? __('messages.empty_filtered') : null" /></td></tr>
                @endforelse
            </tbody>
        </table>
        <x-slot:footer>{{ $applicants->links() }}</x-slot:footer>
    </x-admin.table-card>
</div>
@endsection
