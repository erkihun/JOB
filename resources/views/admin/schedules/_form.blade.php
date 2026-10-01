@php
    $isEdit = $schedule->exists;
    $vacancyLabels = $vacancies->mapWithKeys(fn ($v) => [$v->id => trim(($v->code ? $v->code.' — ' : '').$v->title)]);
    $typeMeta = [
        'exam' => [__('messages.type_exam_hint'), 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
        'interview' => [__('messages.type_interview_hint'), 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
        'practical' => [__('messages.type_practical_hint'), 'M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085'],
    ];
    // Create: pick one or more vacancies (one schedule each). Edit: a schedule keeps its single vacancy.
    $selectedVacancies = array_map('strval', (array) old('vacancy_ids', $schedule->vacancy_id ? [$schedule->vacancy_id] : []));
    $typeLabels = collect($types)->mapWithKeys(fn ($t) => [$t->value => $t->getLabel()]);
@endphp

<div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]"
     x-data="{
        type: @js(old('type', $schedule->type?->value ?? 'exam')),
        title: @js((string) old('title', $schedule->title ?? '')),
        vacancy: @js((string) old('vacancy_id', $schedule->vacancy_id ?? '')),
        selected: @js($selectedVacancies),
        search: '',
        multi: @js(! $isEdit),
        matches(id) { return !this.search || (this.vacancies[id] || '').toLowerCase().includes(this.search.toLowerCase()); },
        get visibleIds() { return Object.keys(this.vacancies).filter(id => this.matches(id)); },
        get allVisibleSelected() { return this.visibleIds.length > 0 && this.visibleIds.every(id => this.selected.includes(id)); },
        toggleAll() {
            if (this.allVisibleSelected) { this.selected = this.selected.filter(id => !this.visibleIds.includes(id)); }
            else { this.selected = [...new Set([...this.selected, ...this.visibleIds])]; }
        },
        get chosen() { return this.multi ? this.selected : (this.vacancy ? [this.vacancy] : []); },
        date: @js((string) old('date', $schedule->date?->format('Y-m-d') ?? '')),
        dateDisplay: '',
        start: @js((string) old('start_time', $schedule->start_time ? substr($schedule->start_time, 0, 5) : '')),
        end: @js((string) old('end_time', $schedule->end_time ? substr($schedule->end_time, 0, 5) : '')),
        venue: @js((string) old('venue', $schedule->venue ?? '')),
        instruction: @js((string) old('instruction', $schedule->instruction ?? '')),
        vacancies: @js($vacancyLabels),
        types: @js($typeLabels),
        notSet: @js(__('messages.not_set')),
        get vacancyLabel() {
            if (this.chosen.length === 0) return this.notSet;
            if (this.chosen.length === 1) return this.vacancies[this.chosen[0]];
            return @js(__('messages.vacancies_selected')).replace(':count', this.chosen.length);
        },
        get suggestedTitle() {
            if (this.chosen.length === 0) return '';
            if (this.chosen.length > 1) return this.types[this.type];
            const v = this.vacancies[this.chosen[0]] || '';
            return this.types[this.type] + ' — ' + v.replace(/^[^—]+—\s*/, '');
        },
        get dateLabel() {
            if (!this.date) return this.notSet;
            // Amharic picker: show the Ethiopian-calendar date the user chose.
            if (this.dateDisplay) return this.dateDisplay;
            const d = new Date(this.date + 'T00:00:00');
            return isNaN(d) ? this.date : d.toLocaleDateString(@js(app()->getLocale() === 'am' ? 'am-ET' : 'en-GB'), { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        },
        get duration() {
            if (!this.start || !this.end) return '';
            const [sh, sm] = this.start.split(':').map(Number), [eh, em] = this.end.split(':').map(Number);
            const mins = (eh * 60 + em) - (sh * 60 + sm);
            if (mins <= 0) return '';
            return (mins >= 60 ? Math.floor(mins / 60) + 'h ' : '') + (mins % 60 ? (mins % 60) + 'm' : '').trim();
        },
        get endBeforeStart() { return this.start && this.end && this.end <= this.start; },
        // Ethiopian date picker writes a hidden input; pick its value up too.
        syncDate(e) {
            if (e.target.name !== 'date') return;
            this.date = e.target.value;
            this.dateDisplay = e.target.dataset?.display || '';
        }
     }"
     @input="syncDate($event)" @change="syncDate($event)">

    {{-- ══ Main column ══ --}}
    <div class="space-y-6">

        {{-- 1 · What --}}
        <x-admin.form-section number="1" :title="__('messages.schedule_section_what')" :description="__('messages.schedule_section_what_hint')">
            <fieldset>
                <legend class="form-label">{{ __('dashboard.table.type') }} <span class="form-required">*</span></legend>
                <div class="grid gap-3 sm:grid-cols-3">
                    @foreach($types as $t)
                    @php [$hint, $icon] = $typeMeta[$t->value] ?? ['', 'M12 6v6l4 2']; @endphp
                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border-2 p-4 transition"
                           :class="type === '{{ $t->value }}' ? 'border-brand bg-brand-muted' : 'border-gray-200 hover:border-gray-300'">
                        <input type="radio" name="type" value="{{ $t->value }}" x-model="type" class="sr-only" @checked(old('type', $schedule->type?->value ?? 'exam') === $t->value)>
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
                              :class="type === '{{ $t->value }}' ? 'bg-brand text-white' : 'bg-gray-100 text-gray-600'">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $icon }}"/></svg>
                        </span>
                        <span>
                            <span class="block text-sm font-semibold text-gray-900">{{ $t->getLabel() }}</span>
                            <span class="mt-0.5 block text-[13px] text-gray-600">{{ $hint }}</span>
                        </span>
                    </label>
                    @endforeach
                </div>
                @error('type')<p class="form-error">{{ $message }}</p>@enderror
            </fieldset>

            @if($isEdit)
            <div>
                <label for="vacancy_id" class="form-label">{{ __('menus.vacancies') }} <span class="form-required">*</span></label>
                <select id="vacancy_id" name="vacancy_id" x-model="vacancy" class="form-select @error('vacancy_id') form-input-error @enderror">
                    <option value="">{{ __('messages.select_vacancy') }}</option>
                    @foreach($vacancies as $v)
                    <option value="{{ $v->id }}" @selected((string) old('vacancy_id', $schedule->vacancy_id ?? '') === (string) $v->id)>{{ $vacancyLabels[$v->id] }}</option>
                    @endforeach
                </select>
                <p class="form-hint">{{ __('messages.schedule_vacancy_hint') }}</p>
                @error('vacancy_id')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            @else
            {{-- Several vacancies at once: one schedule is created for each --}}
            <fieldset>
                <div class="mb-1.5 flex flex-wrap items-end justify-between gap-2">
                    <legend class="form-label mb-0">{{ __('menus.vacancies') }} <span class="form-required">*</span></legend>
                    <span class="text-[13px] font-semibold" :class="selected.length ? 'text-brand' : 'text-gray-600'"
                          x-text="@js(__('messages.vacancies_selected')).replace(':count', selected.length)"></span>
                </div>
                <div class="overflow-hidden rounded-lg border @error('vacancy_ids') border-red-500 @else border-gray-300 @enderror">
                    <div class="flex items-center gap-2 border-b border-gray-200 bg-gray-50 px-3 py-2">
                        <label class="flex shrink-0 cursor-pointer items-center gap-2 text-sm font-semibold text-gray-800">
                            <input type="checkbox" class="form-check" :checked="allVisibleSelected" @change="toggleAll()"
                                   :disabled="visibleIds.length === 0">
                            {{ __('messages.select_all') }}
                        </label>
                        <label for="vacancy-search" class="sr-only">{{ __('messages.search') }}</label>
                        <input type="search" id="vacancy-search" x-model="search" placeholder="{{ __('messages.search_vacancies_placeholder') }}"
                               class="ml-auto h-8 w-full max-w-60 rounded-md border border-gray-300 bg-white px-2.5 text-sm focus:border-brand focus:ring-2 focus:ring-brand/15 focus:outline-none">
                    </div>
                    <ul class="max-h-64 divide-y divide-gray-100 overflow-y-auto bg-white" role="list">
                        @forelse($vacancies as $v)
                        <li x-show="matches(@js((string) $v->id))">
                            <label class="flex cursor-pointer items-center gap-3 px-3 py-2.5 text-sm hover:bg-gray-50">
                                <input type="checkbox" name="vacancy_ids[]" value="{{ $v->id }}" x-model="selected" class="form-check"
                                       @checked(in_array((string) $v->id, $selectedVacancies, true))>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-gray-900">{{ $v->title }}</span>
                                    @if($v->code)<span class="font-mono text-xs text-gray-600">{{ $v->code }}</span>@endif
                                </span>
                            </label>
                        </li>
                        @empty
                        <li class="px-3 py-6 text-center text-sm text-gray-600">{{ __('messages.no_records') }}</li>
                        @endforelse
                        <li x-show="visibleIds.length === 0 && search" x-cloak class="px-3 py-6 text-center text-sm text-gray-600">{{ __('messages.no_records') }}</li>
                    </ul>
                </div>
                <p class="form-hint" x-show="selected.length > 1" x-cloak
                   x-text="@js(__('messages.schedules_will_be_created')).replace(':count', selected.length)"></p>
                <p class="form-hint" x-show="selected.length <= 1">{{ __('messages.schedule_vacancy_hint') }}</p>
                @error('vacancy_ids')<p class="form-error">{{ $message }}</p>@enderror
                @error('vacancy_ids.*')<p class="form-error">{{ $message }}</p>@enderror
            </fieldset>
            @endif

            <div>
                <label for="title" class="form-label">{{ __('messages.title') }} <span class="form-required">*</span></label>
                <input type="text" id="title" name="title" x-model="title" maxlength="255"
                       :placeholder="suggestedTitle || @js(__('messages.schedule_title_placeholder'))"
                       class="form-input @error('title') form-input-error @enderror">
                <button type="button" x-show="!title && suggestedTitle" x-cloak @click="title = suggestedTitle"
                        class="link-action mt-1.5 text-[13px]">
                    {{ __('messages.use_suggested_title') }}: <span x-text="suggestedTitle"></span>
                </button>
                @error('title')<p class="form-error">{{ $message }}</p>@enderror
            </div>
        </x-admin.form-section>

        {{-- 2 · When & where --}}
        <x-admin.form-section number="2" :title="__('messages.schedule_section_when')" :description="__('messages.schedule_section_when_hint')">
            <div class="grid gap-5 sm:grid-cols-3">
                <div>
                    @if(app()->getLocale() === 'am')
                        <x-ethiopian-datepicker name="date" :label="__('dashboard.table.date')"
                            :value="old('date', $schedule->date?->format('Y-m-d') ?? '')"
                            :min="$isEdit ? null : now()->toDateString()" required/>
                    @else
                        <label for="date" class="form-label">{{ __('dashboard.table.date') }} <span class="form-required">*</span></label>
                        <input type="date" id="date" name="date" x-model="date"
                               @unless($isEdit) min="{{ now()->toDateString() }}" @endunless
                               class="form-input @error('date') form-input-error @enderror">
                        @error('date')<p class="form-error">{{ $message }}</p>@enderror
                    @endif
                </div>
                <div>
                    <label for="start_time" class="form-label">{{ __('messages.start_time') }} <span class="form-required">*</span></label>
                    <input type="time" id="start_time" name="start_time" x-model="start"
                           class="form-input @error('start_time') form-input-error @enderror">
                    @error('start_time')<p class="form-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="end_time" class="form-label">{{ __('messages.end_time') }}</label>
                    <input type="time" id="end_time" name="end_time" x-model="end"
                           :class="endBeforeStart ? 'form-input-error' : ''"
                           class="form-input @error('end_time') form-input-error @enderror">
                    <p class="form-hint" x-show="duration && !endBeforeStart" x-cloak>{{ __('messages.duration') }}: <strong x-text="duration"></strong></p>
                    <p class="form-error" x-show="endBeforeStart" x-cloak>{{ __('messages.end_after_start') }}</p>
                    @error('end_time')<p class="form-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label for="venue" class="form-label">{{ __('dashboard.table.venue') }} <span class="form-required">*</span></label>
                <input type="text" id="venue" name="venue" x-model="venue" maxlength="255"
                       placeholder="{{ __('messages.venue_placeholder') }}"
                       class="form-input @error('venue') form-input-error @enderror">
                @error('venue')<p class="form-error">{{ $message }}</p>@enderror
            </div>
        </x-admin.form-section>

        {{-- 3 · Instructions --}}
        <x-admin.form-section number="3" :title="__('messages.instructions')" :description="__('messages.instructions_hint')">
            <div>
                <label for="instruction" class="sr-only">{{ __('messages.instructions') }}</label>
                <textarea id="instruction" name="instruction" rows="5" maxlength="5000" x-model="instruction"
                          placeholder="{{ __('messages.instructions_placeholder') }}"
                          class="form-textarea @error('instruction') form-input-error @enderror"></textarea>
                <p class="form-hint text-right"><span x-text="instruction.length"></span> / 5000</p>
                @error('instruction')<p class="form-error">{{ $message }}</p>@enderror
            </div>
        </x-admin.form-section>
    </div>

    {{-- ══ Sidebar: live invitation preview + actions ══ --}}
    <aside class="lg:sticky lg:top-20 lg:self-start">
        <section class="card" aria-labelledby="invitation-preview">
            <div class="card-header">
                <div>
                    <h2 id="invitation-preview" class="card-title">{{ __('messages.invitation_preview') }}</h2>
                    <p class="card-description">{{ __('messages.invitation_preview_hint') }}</p>
                </div>
            </div>
            <div class="card-body space-y-4">
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                    <span class="badge-info badge-dot" x-text="types[type]"></span>
                    <p class="mt-2 text-[15px] font-semibold text-gray-900" x-text="title || suggestedTitle || notSet"></p>
                    <p class="mt-0.5 text-[13px] text-gray-600" x-text="vacancyLabel"></p>
                    <dl class="mt-4 space-y-2.5 text-sm">
                        @foreach([
                            ['M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', __('dashboard.table.date'), 'dateLabel'],
                            ['M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', __('dashboard.table.time'), "(start || notSet) + (end && !endBeforeStart ? ' – ' + end : '')"],
                            ['M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z', __('dashboard.table.venue'), 'venue || notSet'],
                        ] as [$icon, $label, $expr])
                        <div class="flex gap-2.5">
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $icon }}"/></svg>
                            <div class="min-w-0">
                                <dt class="sr-only">{{ $label }}</dt>
                                <dd class="text-gray-900" x-text="{{ $expr }}"></dd>
                            </div>
                        </div>
                        @endforeach
                    </dl>
                    <p class="mt-3 line-clamp-4 whitespace-pre-line border-t border-gray-200 pt-3 text-[13px] text-gray-700" x-show="instruction" x-cloak x-text="instruction"></p>
                </div>

                <div class="flex flex-col gap-2">
                    <button type="submit" class="btn btn-primary w-full" :disabled="endBeforeStart">
                        {{ $isEdit ? __('messages.save_changes') : __('messages.add_schedule') }}
                    </button>
                    <a href="{{ route('admin.schedules.index') }}" class="btn btn-secondary w-full">{{ __('messages.cancel') }}</a>
                </div>
                <p class="text-[13px] text-gray-600">{{ __('messages.schedule_next_step') }}</p>
            </div>
        </section>
    </aside>
</div>
