@extends('layouts.admin')
@section('title', __('menus.schedules'))
@section('content')
@php
    $locale = app()->getLocale();
    $typeStyles = [
        'exam'      => ['badge-info', 'border-l-blue-600 bg-blue-50 text-blue-900'],
        'interview' => ['badge-warning', 'border-l-amber-500 bg-amber-50 text-amber-900'],
        'practical' => ['badge-success', 'border-l-emerald-600 bg-emerald-50 text-emerald-900'],
    ];
    $user = auth()->user();
    $canRecord = fn ($schedule) => $user->hasAnyRole(['super_admin', 'admin', 'hr_manager'])
        || $user->hasPermissionTo($schedule->type->isExamLike() ? 'exams.record-results' : 'interviews.record-results');
    $keep = request()->only(['vacancy_id', 'type']);
    $monthUrl = fn ($m) => route('admin.schedules.index', $keep + ['view' => 'calendar', 'month' => $m->format('Y-m')]);
    $weekdays = collect(range(0, 6))->map(fn ($i) => $gridStart->copy()->addDays($i)->locale($locale)->translatedFormat('D'));
    $monthTotal = $calendarSchedules->filter(fn ($items, $date) => \Illuminate\Support\Str::startsWith($date, $month->format('Y-m')))->flatten()->count();
@endphp
<div class="space-y-5">

    <x-admin.page-header :title="__('menus.schedules')" :description="__('messages.schedules_intro')">
        <a href="{{ route('admin.schedules.create') }}" class="btn btn-primary">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
            {{ __('messages.add_schedule') }}
        </a>
    </x-admin.page-header>

    {{-- Filters + view switch --}}
    <form method="GET" class="filter-bar">
        <input type="hidden" name="view" value="{{ $view }}">
        @if($view === 'calendar')<input type="hidden" name="month" value="{{ $month->format('Y-m') }}">@endif
        <div class="filter-field-lg">
            <label for="f-vacancy" class="form-label">{{ __('menus.vacancies') }}</label>
            <select id="f-vacancy" name="vacancy_id" class="form-select">
                <option value="">{{ __('messages.all_vacancies') }}</option>
                @foreach($vacancies as $v)
                <option value="{{ $v->id }}" @selected(request('vacancy_id') === $v->id)>{{ $v->code }} — {{ $v->title }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter-field">
            <label for="f-type" class="form-label">{{ __('dashboard.table.type') }}</label>
            <select id="f-type" name="type" class="form-select">
                <option value="">{{ __('messages.all_types') }}</option>
                @foreach($types as $t)
                <option value="{{ $t->value }}" @selected(request('type') === $t->value)>{{ $t->getLabel() }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter-actions">
            <button type="submit" class="btn btn-primary">{{ __('messages.filter') }}</button>
            @if(request()->hasAny(['vacancy_id', 'type']))
            <a href="{{ route('admin.schedules.index', ['view' => $view]) }}" class="btn btn-ghost">{{ __('messages.reset') }}</a>
            @endif
        </div>
        <div class="ml-auto flex rounded-lg bg-gray-100 p-0.5 text-sm font-semibold" role="group" aria-label="{{ __('messages.view_mode') }}">
            @foreach(['calendar' => __('messages.calendar'), 'list' => __('messages.list')] as $mode => $label)
            <a href="{{ route('admin.schedules.index', $keep + ['view' => $mode]) }}"
               @if($view === $mode) aria-current="page" @endif
               class="rounded-md px-3 py-1.5 {{ $view === $mode ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">{{ $label }}</a>
            @endforeach
        </div>
    </form>

    @if($view === 'calendar')
    {{-- ══════════════════ Calendar ══════════════════ --}}
    <section class="card overflow-hidden" aria-labelledby="calendar-title">
        <div class="card-header">
            <div class="flex items-center gap-2">
                <a href="{{ $monthUrl($month->copy()->subMonth()) }}" class="btn btn-secondary btn-sm btn-icon" aria-label="{{ __('public.previous') }}">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M15 19l-7-7 7-7"/></svg>
                </a>
                <h2 id="calendar-title" class="card-title min-w-44 text-center">{{ $month->copy()->locale($locale)->translatedFormat('F Y') }}</h2>
                <a href="{{ $monthUrl($month->copy()->addMonth()) }}" class="btn btn-secondary btn-sm btn-icon" aria-label="{{ __('public.next') }}">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M9 5l7 7-7 7"/></svg>
                </a>
                @unless($month->isSameMonth(now()))
                <a href="{{ $monthUrl(now()) }}" class="btn btn-ghost btn-sm">{{ __('messages.today') }}</a>
                @endunless
            </div>
            <div class="flex flex-wrap items-center gap-3 text-[13px] text-gray-700">
                <span>{{ trans_choice('messages.sessions_this_month', $monthTotal, ['count' => $monthTotal]) }}</span>
                @foreach($types as $t)
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm border-l-4 {{ $typeStyles[$t->value][1] ?? '' }}"></span>{{ $t->getLabel() }}</span>
                @endforeach
            </div>
        </div>

        {{-- Month grid (tablet and up) --}}
        <div class="hidden md:block">
            <div class="grid grid-cols-7 border-b border-gray-200 bg-gray-50">
                @foreach($weekdays as $day)
                <div class="px-2 py-2 text-center text-xs font-semibold uppercase tracking-wide text-gray-600">{{ $day }}</div>
                @endforeach
            </div>
            <div class="grid grid-cols-7">
                @for($day = $gridStart->copy(); $day->lte($gridEnd); $day->addDay())
                @php
                    $items = $calendarSchedules->get($day->toDateString(), collect());
                    $inMonth = $day->isSameMonth($month);
                    $isToday = $day->isToday();
                @endphp
                <div class="min-h-28 border-b border-r border-gray-100 p-1.5 {{ $inMonth ? 'bg-white' : 'bg-gray-50' }} {{ $day->dayOfWeekIso === 7 ? 'border-r-0' : '' }}">
                    <div class="mb-1 flex items-center justify-between px-1">
                        <span class="flex h-6 min-w-6 items-center justify-center rounded-full px-1 text-[13px] font-semibold
                                     {{ $isToday ? 'bg-brand text-white' : ($inMonth ? 'text-gray-900' : 'text-gray-500') }}"
                              @if($isToday) aria-current="date" @endif>{{ $day->day }}</span>
                        @if($locale === 'am' && $inMonth)
                        <span class="text-[11px] text-gray-500">{{ et_date($day, 'd M') }}</span>
                        @endif
                    </div>
                    <ul class="space-y-1">
                        @foreach($items->take(3) as $s)
                        <li>
                            <a href="{{ $canRecord($s) ? route('admin.schedules.results', $s) : route('admin.schedules.edit', $s) }}"
                               title="{{ $s->title }} · {{ $s->vacancy?->title }} · {{ $s->venue }}"
                               class="block truncate rounded border-l-4 px-1.5 py-1 text-xs font-medium hover:brightness-95 {{ $typeStyles[$s->type->value][1] ?? '' }}">
                                <span class="font-semibold tabular-nums">{{ substr((string) $s->start_time, 0, 5) }}</span> {{ $s->title }}
                            </a>
                        </li>
                        @endforeach
                        @if($items->count() > 3)
                        <li class="px-1.5 text-xs font-semibold text-gray-700">{{ __('messages.more_count', ['count' => $items->count() - 3]) }}</li>
                        @endif
                    </ul>
                </div>
                @endfor
            </div>
        </div>

        {{-- Agenda (phones) --}}
        <div class="divide-y divide-gray-100 md:hidden">
            @forelse($calendarSchedules->filter(fn ($i, $date) => \Illuminate\Support\Str::startsWith($date, $month->format('Y-m'))) as $date => $items)
            @php $d = \Illuminate\Support\Carbon::parse($date); @endphp
            <div class="flex gap-3 px-4 py-3">
                <div class="w-12 shrink-0 text-center {{ $d->isToday() ? 'text-brand' : 'text-gray-900' }}">
                    <p class="text-xs font-semibold uppercase">{{ $d->copy()->locale($locale)->translatedFormat('D') }}</p>
                    <p class="text-xl font-bold">{{ $d->day }}</p>
                </div>
                <ul class="min-w-0 flex-1 space-y-1.5">
                    @foreach($items as $s)
                    <li>
                        <a href="{{ $canRecord($s) ? route('admin.schedules.results', $s) : route('admin.schedules.edit', $s) }}"
                           class="block rounded border-l-4 px-2.5 py-1.5 text-sm {{ $typeStyles[$s->type->value][1] ?? '' }}">
                            <span class="font-semibold">{{ substr((string) $s->start_time, 0, 5) }} · {{ $s->title }}</span>
                            <span class="block truncate text-xs opacity-80">{{ $s->vacancy?->title }} · {{ $s->venue }}</span>
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>
            @empty
            <x-admin.empty :title="__('messages.no_sessions_this_month')" />
            @endforelse
        </div>
    </section>

    @else
    {{-- ══════════════════ List ══════════════════ --}}
    <div class="table-wrap">
        <div class="table-scroll">
            <table class="min-w-full">
                <thead class="table-header">
                    <tr>
                        <th class="table-th">{{ __('messages.title') }}</th>
                        <th class="table-th">{{ __('dashboard.table.type') }}</th>
                        <th class="table-th hidden sm:table-cell">{{ __('menus.vacancies') }}</th>
                        <th class="table-th">{{ __('dashboard.table.date') }}</th>
                        <th class="table-th hidden md:table-cell">{{ __('dashboard.table.venue') }}</th>
                        <th class="table-th-right">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($schedules as $schedule)
                    <tr class="table-row">
                        <td class="table-td font-semibold">{{ $schedule->title }}</td>
                        <td class="table-td"><span class="{{ $typeStyles[$schedule->type->value][0] ?? 'badge-gray' }} badge-dot">{{ $schedule->type->getLabel() }}</span></td>
                        <td class="table-td table-td-muted hidden sm:table-cell">{{ $schedule->vacancy?->title }}</td>
                        <td class="table-td tabular-nums">
                            {{ et_date($schedule->date) }}
                            <span class="block text-[13px] text-gray-600">{{ substr((string) $schedule->start_time, 0, 5) }}@if($schedule->end_time) – {{ substr((string) $schedule->end_time, 0, 5) }}@endif</span>
                        </td>
                        <td class="table-td table-td-muted hidden md:table-cell">{{ $schedule->venue ?? '—' }}</td>
                        <td class="table-td">
                            <div class="table-actions">
                                @if($canRecord($schedule))
                                <a href="{{ route('admin.schedules.results', $schedule) }}" class="btn btn-secondary btn-sm">{{ __('messages.results') }}</a>
                                @endif
                                <a href="{{ route('admin.schedules.edit', $schedule) }}" class="btn btn-secondary btn-sm">{{ __('messages.edit') }}</a>
                                <form method="POST" action="{{ route('admin.schedules.destroy', $schedule) }}" onsubmit="return confirm(@js(__('messages.confirm_delete')))">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger-soft btn-sm">{{ __('messages.delete') }}</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6"><x-admin.empty /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($schedules->hasPages())
        <div class="table-footer">{{ $schedules->links() }}</div>
        @endif
    </div>
    @endif
</div>
@endsection
