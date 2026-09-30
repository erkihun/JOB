@extends('layouts.admin')
@section('title', __('menus.applicants'))
@section('breadcrumb') {{ __('menus.applicants') }} @endsection

@section('content')
<div class="space-y-4">

    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="page-title">{{ __('menus.applicants') }}</h1>
            <p class="text-sm text-gray-500">{{ __('messages.total') }}: {{ $applicants->total() }}</p>
        </div>
    </div>

    {{-- Search --}}
    <form method="GET" action="{{ route('admin.applicants.index') }}" class="filter-bar">
        <input type="text" name="search" value="{{ $search }}"
               placeholder="{{ __('messages.search_applicants') }}"
               class="form-input flex-1 max-w-sm">
        <button type="submit" class="btn btn-primary">{{ __('messages.search') }}</button>
        @if($search)
            <a href="{{ route('admin.applicants.index') }}" class="btn btn-secondary">{{ __('messages.clear') }}</a>
        @endif
    </form>

    {{-- Table --}}
    <div class="card overflow-hidden">
        <table class="min-w-full divide-y divide-gray-100 text-sm">
            <thead class="table-header">
                <tr>
                    <th class="table-th">{{ __('fields.applicant_code') }}</th>
                    <th class="table-th">{{ __('fields.full_name') }}</th>
                    <th class="table-th">{{ __('fields.email') }}</th>
                    <th class="table-th">{{ __('fields.phone') }}</th>
                    <th class="table-th">{{ __('fields.gender') }}</th>
                    <th class="px-4 py-3 text-center">{{ __('menus.applications') }}</th>
                    <th class="table-th">{{ __('fields.registered_at') }}</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($applicants as $applicant)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ $applicant->applicant_code ?: '—' }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2.5">
                            @if($applicant->profile_photo_path)
                                <img src="{{ route('admin.applicants.photo', $applicant) }}" class="h-8 w-8 rounded-full object-cover shrink-0" alt="">
                            @else
                                <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center shrink-0">
                                    <span class="text-xs font-bold text-blue-600">{{ strtoupper(substr($applicant->first_name ?? '?', 0, 1)) }}</span>
                                </div>
                            @endif
                            <span class="font-medium text-gray-900">{{ $applicant->full_name ?: '—' }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ $applicant->email }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $applicant->phone ?: '—' }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $applicant->gender?->label() ?? '—' }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex items-center justify-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700">
                            {{ $applicant->applications_count }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ et_date($applicant->created_at) }}</td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('admin.applicants.show', $applicant) }}"
                           class="link-action">{{ __('messages.view') }}</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-10 text-center text-sm text-gray-600">{{ __('messages.no_applicants_found') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($applicants->hasPages())
        <div>{{ $applicants->links() }}</div>
    @endif

</div>
@endsection
