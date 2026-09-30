@extends('layouts.admin')

@section('title', __('menus.final_results'))

@section('content')
@php
    $canAnnounce = auth()->user()?->hasAnyRole(['super_admin', 'admin', 'hr_manager'])
        || auth()->user()?->hasPermissionTo('notifications.send');
    $announceable = $applications->filter(fn ($a) => $a->finalResult !== null);
    $showSelect = $canAnnounce && $announceable->isNotEmpty();
    $decision = fn ($d) => match ($d) {
        'selected'     => ['success', __('messages.selected')],
        'waitlisted'   => ['warning', __('messages.waitlisted')],
        'not_selected' => ['danger', __('messages.not_selected')],
        default        => ['gray', __('messages.pending')],
    };
    $score = fn ($v) => $v !== null ? number_format((float) $v, 2) : '—';
@endphp
<div class="space-y-6">
    <x-admin.page-header :title="__('menus.final_results')"
                         :description="__('messages.final_results_hint')"
                         :crumbs="[['label' => __('menus.exams_interviews')], ['label' => __('menus.final_results')]]" />

    <x-admin.filters :action="route('admin.final-results.index')" :reset="route('admin.final-results.index')" :active="filled($vacancyId)">
        <div class="lg:col-span-3">
            <label for="vacancy_id" class="form-label">{{ __('menus.vacancies') }}</label>
            <select id="vacancy_id" name="vacancy_id" class="form-select">
                <option value="">{{ __('messages.all_vacancies') }}</option>
                @foreach($vacancies as $vacancy)
                <option value="{{ $vacancy->id }}" @selected($vacancyId === $vacancy->id)>{{ $vacancy->code }} · {{ $vacancy->title }}</option>
                @endforeach
            </select>
        </div>
    </x-admin.filters>

    {{-- Announce final results to applicants --}}
    @if($showSelect)
    <form id="announce-form" method="POST" action="{{ route('admin.final-results.announce') }}"
          x-data="{ count: 0 }"
          @change.window="count = document.querySelectorAll('input[form=announce-form][name=\'application_ids[]\']:checked').length"
          @submit="if (count === 0 || !confirm(@js(__('messages.announce_confirm')))) $event.preventDefault()"
          class="card border-brand/30">
        @csrf
        <div class="card-header">
            <div>
                <h2 class="card-title">{{ __('messages.announce_results') }}</h2>
                <p class="card-description">{{ __('messages.announce_results_hint') }}</p>
            </div>
            <span class="badge badge-blue"><span x-text="count">0</span>&nbsp;{{ __('messages.selected_count') }}</span>
        </div>
        <div class="card-body grid gap-3 md:grid-cols-[12rem_1fr_auto] md:items-end">
            <div>
                <label for="announce_channel" class="form-label">{{ __('messages.channel') }}</label>
                <select id="announce_channel" name="channel" class="form-select">
                    <option value="email">{{ __('messages.channel_email') }}</option>
                    <option value="in_system">{{ __('messages.channel_in_system') }}</option>
                </select>
            </div>
            <div>
                <label for="announce_message" class="form-label">{{ __('messages.announce_message') }}</label>
                <input id="announce_message" name="message" type="text" maxlength="5000" class="form-input" placeholder="{{ __('messages.announce_message_placeholder') }}">
            </div>
            <button type="submit" class="btn btn-primary justify-center" :disabled="count === 0">{{ __('messages.announce_selected') }}</button>
        </div>
        @error('application_ids')<p class="form-error px-5 pb-4">{{ $message }}</p>@enderror
    </form>
    @endif

    <x-admin.table-card :meta="trans_choice('messages.records_count', $applications->total(), ['count' => number_format($applications->total())])">
        <table class="min-w-full divide-y divide-gray-100 text-sm">
            <thead class="table-header">
                <tr>
                    @if($showSelect)
                    <th class="table-th w-10">
                        <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-brand focus:ring-brand" aria-label="{{ __('messages.select_all') }}"
                               onclick="document.querySelectorAll('input[form=announce-form][name=\'application_ids[]\']').forEach(cb => cb.checked = this.checked); this.dispatchEvent(new Event('change', { bubbles: true }))">
                    </th>
                    @endif
                    <th class="table-th">{{ __('messages.applicant') }}</th>
                    <th class="table-th hidden md:table-cell">{{ __('menus.vacancies') }}</th>
                    <th class="table-th-right hidden lg:table-cell">{{ __('messages.exam_score') }}</th>
                    <th class="table-th-right hidden lg:table-cell">{{ __('messages.interview_score') }}</th>
                    <th class="table-th-right hidden xl:table-cell">{{ __('messages.practical_score') }}</th>
                    <th class="table-th-right">{{ __('messages.final_score') }}</th>
                    <th class="table-th">{{ __('messages.decision') }}</th>
                    <th class="table-th-right"><span class="sr-only">{{ __('messages.actions') }}</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($applications as $application)
                @php $result = $application->finalResult; [$tone, $label] = $decision($result?->decision); @endphp
                <tr class="table-row">
                    @if($showSelect)
                    <td class="table-td">
                        @if($result)
                        <input type="checkbox" form="announce-form" name="application_ids[]" value="{{ $application->id }}"
                               class="h-4 w-4 rounded border-gray-300 text-brand focus:ring-brand" aria-label="{{ $application->reference_number }}">
                        @endif
                    </td>
                    @endif
                    <td class="table-td">
                        <div class="flex items-center gap-3">
                            <x-admin.avatar :name="$application->applicant?->full_name" />
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-gray-900">{{ $application->applicant?->full_name ?? '—' }}</p>
                                <p class="font-mono text-xs text-gray-600">{{ $application->reference_number }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="table-td table-td-muted hidden md:table-cell">{{ $application->vacancy?->title }}</td>
                    <td class="table-td hidden text-right tabular-nums lg:table-cell">{{ $score($result?->exam_score) }}</td>
                    <td class="table-td hidden text-right tabular-nums lg:table-cell">{{ $score($result?->interview_score) }}</td>
                    <td class="table-td hidden text-right tabular-nums xl:table-cell">{{ $score($result?->practical_score) }}</td>
                    <td class="table-td text-right font-bold tabular-nums text-gray-900">{{ $score($result?->final_score) }}</td>
                    <td class="table-td"><x-admin.status :tone="$tone" :label="$label" /></td>
                    <td class="table-td">
                        <div class="table-actions">
                            @if($result)
                            <a href="{{ route('admin.final-results.edit', $application) }}" class="btn btn-secondary btn-sm">{{ __('messages.edit') }}</a>
                            @else
                            <a href="{{ route('admin.final-results.create', $application) }}" class="btn btn-primary btn-sm">{{ __('messages.add_result') }}</a>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="9"><x-admin.empty :text="__('messages.final_empty_hint')" /></td></tr>
                @endforelse
            </tbody>
        </table>
        <x-slot:footer>{{ $applications->links() }}</x-slot:footer>
    </x-admin.table-card>
</div>
@endsection
