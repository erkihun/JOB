@extends('layouts.admin')

@section('title', __('menus.final_results'))

@section('content')
<div class="space-y-5">
    <div>
        <h1 class="page-title">{{ __('menus.final_results') }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ __('messages.final_results_hint') }}</p>
    </div>

    {{-- Vacancy filter --}}
    <form method="GET" action="{{ route('admin.final-results.index') }}" class="card card-body">
        <label class="block text-sm font-medium text-gray-700">{{ __('menus.vacancies') }}</label>
        <div class="mt-2 flex flex-wrap gap-3">
            <select name="vacancy_id" class="form-select max-w-xl">
                <option value="">{{ __('messages.all_vacancies') }}</option>
                @foreach($vacancies as $vacancy)
                    <option value="{{ $vacancy->id }}" @selected($vacancyId === $vacancy->id)>
                        {{ $vacancy->code }} · {{ $vacancy->title }}
                    </option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-navy">{{ __('messages.filter') }}</button>
        </div>
    </form>

    @php
        $canAnnounce = auth()->user()?->hasAnyRole(['super_admin', 'admin', 'hr_manager'])
            || auth()->user()?->hasPermissionTo('notifications.send');
        $announceable = $applications->filter(fn ($a) => $a->finalResult !== null);
    @endphp

    {{-- Announce final results to applicants --}}
    @if($canAnnounce && $announceable->isNotEmpty())
    <form id="announce-form" method="POST" action="{{ route('admin.final-results.announce') }}"
          x-data="{ count: 0 }"
          @change.window="count = document.querySelectorAll('input[form=announce-form][name=\'application_ids[]\']:checked').length"
          @submit="if (count === 0 || !confirm(@js(__('messages.announce_confirm')))) $event.preventDefault()"
          class="rounded-xl border border-brand/20 bg-brand-muted/40 p-5 shadow-sm">
        @csrf
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="card-title">{{ __('messages.announce_results') }}</h2>
                <p class="mt-1 text-xs text-gray-500">{{ __('messages.announce_results_hint') }}</p>
            </div>
            <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-brand ring-1 ring-brand/20">
                <span x-text="count">0</span> {{ __('messages.selected_count') }}
            </span>
        </div>
        <div class="mt-4 grid gap-3 md:grid-cols-[12rem_1fr_auto] md:items-end">
            <div>
                <label for="announce_channel" class="block text-xs font-medium text-gray-600">{{ __('messages.channel') }}</label>
                <select id="announce_channel" name="channel" class="form-select mt-1">
                    <option value="email">{{ __('messages.channel_email') }}</option>
                    <option value="in_system">{{ __('messages.channel_in_system') }}</option>
                </select>
            </div>
            <div>
                <label for="announce_message" class="block text-xs font-medium text-gray-600">{{ __('messages.announce_message') }}</label>
                <input id="announce_message" name="message" type="text" maxlength="5000" class="form-input mt-1"
                       placeholder="{{ __('messages.announce_message_placeholder') }}">
            </div>
            <button type="submit" class="btn btn-primary justify-center" :disabled="count === 0">
                {{ __('messages.announce_selected') }}
            </button>
        </div>
        @error('application_ids')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
    </form>
    @endif

    {{-- Applicants table --}}
    <div class="card overflow-hidden">
        <table class="min-w-full divide-y divide-gray-100 text-sm">
            <thead class="table-header">
                <tr>
                    @if($canAnnounce && $announceable->isNotEmpty())
                    <th class="table-th w-10">
                        <input type="checkbox" class="rounded border-white/40" aria-label="{{ __('messages.select_all') }}"
                               onclick="document.querySelectorAll('input[form=announce-form][name=\'application_ids[]\']').forEach(cb => cb.checked = this.checked); this.dispatchEvent(new Event('change', { bubbles: true }))">
                    </th>
                    @endif
                    <th class="table-th">{{ __('messages.applicant') }}</th>
                    <th class="table-th">{{ __('messages.reference') }}</th>
                    <th class="table-th hidden md:table-cell">{{ __('menus.vacancies') }}</th>
                    <th class="table-th">{{ __('messages.exam_score') }}</th>
                    <th class="table-th">{{ __('messages.interview_score') }}</th>
                    <th class="table-th">{{ __('messages.practical_score') }}</th>
                    <th class="table-th">{{ __('messages.final_score') }}</th>
                    <th class="table-th">{{ __('messages.decision') }}</th>
                    <th class="table-th">{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($applications as $application)
                @php
                    $result = $application->finalResult;
                    $decisionBadge = match($result?->decision) {
                        'selected'     => 'badge-green',
                        'waitlisted'   => 'badge-amber',
                        'not_selected' => 'badge-red',
                        default        => 'badge-gray',
                    };
                    $decisionLabel = match($result?->decision) {
                        'selected'     => __('messages.selected'),
                        'waitlisted'   => __('messages.waitlisted'),
                        'not_selected' => __('messages.not_selected'),
                        default        => __('messages.pending'),
                    };
                @endphp
                <tr class="table-row">
                    @if($canAnnounce && $announceable->isNotEmpty())
                    <td class="table-td">
                        @if($result)
                        <input type="checkbox" form="announce-form" name="application_ids[]" value="{{ $application->id }}"
                               class="rounded border-gray-300 text-brand focus:ring-brand"
                               aria-label="{{ $application->reference_number }}">
                        @endif
                    </td>
                    @endif
                    <td class="table-td">
                        <p class="font-medium text-gray-900">{{ $application->applicant?->full_name ?? '—' }}</p>
                        <p class="mt-0.5 text-xs text-gray-600">{{ $application->applicant?->email ?? $application->applicant?->phone }}</p>
                    </td>
                    <td class="table-td font-mono text-xs text-gray-600">{{ $application->reference_number }}</td>
                    <td class="table-td hidden text-gray-500 md:table-cell">{{ $application->vacancy?->title }}</td>
                    <td class="table-td text-center">
                        {{ $result?->exam_score !== null ? number_format((float)$result->exam_score, 2) : '—' }}
                    </td>
                    <td class="table-td text-center">
                        {{ $result?->interview_score !== null ? number_format((float)$result->interview_score, 2) : '—' }}
                    </td>
                    <td class="table-td text-center">
                        {{ $result?->practical_score !== null ? number_format((float)$result->practical_score, 2) : '—' }}
                    </td>
                    <td class="table-td text-center font-semibold">
                        {{ $result?->final_score !== null ? number_format((float)$result->final_score, 2) : '—' }}
                    </td>
                    <td class="table-td">
                        <span class="{{ $decisionBadge }}">{{ $decisionLabel }}</span>
                    </td>
                    <td class="table-td">
                        @if($result)
                        <a href="{{ route('admin.final-results.edit', $application) }}" class="btn btn-sm btn-outline">{{ __('messages.edit') }}</a>
                        @else
                        <a href="{{ route('admin.final-results.create', $application) }}" class="btn btn-sm btn-primary">{{ __('messages.add_result') }}</a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="px-4 py-10 text-center text-gray-600">{{ __('messages.no_records') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $applications->links() }}
</div>
@endsection
