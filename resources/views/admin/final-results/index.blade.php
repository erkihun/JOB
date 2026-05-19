@extends('layouts.admin')

@section('title', __('menus.final_results'))

@section('content')
<div class="space-y-5">
    <div>
        <h1 class="text-lg font-semibold text-gray-900">{{ __('menus.final_results') }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ __('messages.final_results_hint') }}</p>
    </div>

    {{-- Vacancy filter --}}
    <form method="GET" action="{{ route('admin.final-results.index') }}" class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
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

    {{-- Applicants table --}}
    <div class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-100 text-sm">
            <thead class="table-header">
                <tr>
                    <th class="table-th">{{ __('messages.applicant') }}</th>
                    <th class="table-th">{{ __('messages.reference') }}</th>
                    <th class="table-th hidden md:table-cell">{{ __('menus.vacancies') }}</th>
                    <th class="table-th">{{ __('messages.exam_score') }}</th>
                    <th class="table-th">{{ __('messages.interview_score') }}</th>
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
                    <td class="table-td">
                        <p class="font-medium text-gray-900">{{ $application->applicant?->full_name ?? '—' }}</p>
                        <p class="mt-0.5 text-xs text-gray-400">{{ $application->applicant?->email ?? $application->applicant?->phone }}</p>
                    </td>
                    <td class="table-td font-mono text-xs text-gray-600">{{ $application->reference_number }}</td>
                    <td class="table-td hidden text-gray-500 md:table-cell">{{ $application->vacancy?->title }}</td>
                    <td class="table-td text-center">
                        {{ $result?->exam_score !== null ? number_format((float)$result->exam_score, 2) : '—' }}
                    </td>
                    <td class="table-td text-center">
                        {{ $result?->interview_score !== null ? number_format((float)$result->interview_score, 2) : '—' }}
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
                    <td colspan="8" class="px-4 py-10 text-center text-gray-400">{{ __('messages.no_records') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $applications->links() }}
</div>
@endsection
