@php
    $isEdit = isset($vacancy) && $vacancy->exists;
    $currentStatus = old('status', $vacancy->status?->value ?? 'draft');
    $statusHelp = collect($statuses)->mapWithKeys(fn ($s) => [
        $s->value => __('vacancies.status_help.'.$s->value),
    ]);
@endphp

{{-- ── Page header ── --}}
<div class="mb-5 flex flex-wrap items-start justify-between gap-4">
    <div class="min-w-0">
        <nav class="flex items-center gap-1.5 text-xs font-medium text-gray-500" aria-label="Breadcrumb">
            <a href="{{ route('admin.vacancies.index') }}" class="hover:text-gray-800">{{ __('menus.vacancies') }}</a>
            <svg class="h-3.5 w-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-gray-700">{{ $isEdit ? __('vacancies.edit_vacancy') : __('vacancies.create_vacancy') }}</span>
        </nav>
        <h1 class="mt-1.5 truncate text-xl font-bold tracking-tight text-gray-900 sm:text-2xl">
            {{ $isEdit ? ($vacancy->getTranslation('title', app()->getLocale(), false) ?: $vacancy->getTranslation('title', 'en', false)) : __('vacancies.create_vacancy') }}
        </h1>
        <p class="mt-1 text-sm text-gray-500">{{ __('vacancies.form_intro') }}</p>
    </div>
    @if($isEdit && $vacancy->code)
    <span class="rounded-lg bg-gray-100 px-3 py-1.5 font-mono text-xs font-semibold text-gray-700">{{ $vacancy->code }}</span>
    @endif
</div>

<div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">

    {{-- ══════════════════════════════ Main column ══ --}}
    <div class="space-y-6">

        {{-- 1 · Position --}}
        <x-admin.form-section number="1" :title="__('vacancies.section_position')" :description="__('vacancies.section_position_hint')">

            <x-admin.bilingual-field name="title" :label="__('vacancies.title')" required
                                     :values="['en' => $vacancy->getTranslation('title', 'en', false), 'am' => $vacancy->getTranslation('title', 'am', false)]"
                                     :placeholder="__('vacancies.title_placeholder')" />

            <div class="grid gap-4 sm:grid-cols-2">
                @if(isset($institutions) && $institutions->isNotEmpty())
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700" for="institution_id">{{ __('admin.institution_name') }}</label>
                    <select name="institution_id" id="institution_id" class="form-select mt-1.5 @error('institution_id') form-input-error @enderror">
                        <option value="">— {{ __('admin.institution_select') }} —</option>
                        @foreach($institutions as $inst)
                        <option value="{{ $inst->id }}" @selected(old('institution_id', $vacancy->institution_id ?? '') === $inst->id)>
                            {{ $inst->short_name ? "{$inst->short_name} — {$inst->name}" : $inst->name }}
                        </option>
                        @endforeach
                    </select>
                    @error('institution_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                @endif

                <div>
                    <label class="block text-sm font-medium text-gray-700" for="department">{{ __('vacancies.department') }}</label>
                    <input type="text" id="department" name="department" maxlength="255"
                           value="{{ old('department', $vacancy->department ?? '') }}"
                           class="form-input mt-1.5 @error('department') form-input-error @enderror">
                    @error('department')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700" for="code">
                        {{ __('vacancies.code') }} @unless($autoCode ?? false)<span class="text-red-500">*</span>@endunless
                    </label>
                    @if($autoCode ?? false)
                        <div class="form-input mt-1.5 flex items-center gap-2 bg-gray-50">
                            <span class="font-mono text-sm font-medium text-gray-700">{{ $vacancy->code ?: ($codePreview ?? __('settings.code_auto_generate')) }}</span>
                            <span class="ml-auto rounded-full bg-brand-muted px-2 py-0.5 text-xs font-medium text-brand">{{ __('settings.auto') }}</span>
                        </div>
                    @else
                        <input type="text" id="code" name="code" value="{{ old('code', $vacancy->code ?? '') }}"
                               class="form-input mt-1.5 font-mono @error('code') form-input-error @enderror" placeholder="VAC-2026-0001">
                        @error('code')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    @endif
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700" for="employment_type">{{ __('vacancies.employment_type') }}</label>
                    <select id="employment_type" name="employment_type" class="form-select mt-1.5">
                        <option value="">—</option>
                        @foreach ($employmentTypes as $et)
                        <option value="{{ $et->value }}" @selected(old('employment_type', $vacancy->employment_type?->value ?? '') === $et->value)>{{ $et->getLabel() }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="number_of_positions">
                            {{ __('vacancies.positions') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="number" id="number_of_positions" name="number_of_positions" min="1"
                               value="{{ old('number_of_positions', $vacancy->number_of_positions ?? 1) }}"
                               class="form-input mt-1.5 @error('number_of_positions') form-input-error @enderror">
                        @error('number_of_positions')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="salary_grade">{{ __('vacancies.salary_grade') }}</label>
                        <input type="text" id="salary_grade" name="salary_grade" maxlength="100"
                               value="{{ old('salary_grade', $vacancy->salary_grade ?? '') }}" class="form-input mt-1.5">
                    </div>
                </div>
            </div>

            <x-admin.bilingual-field name="location" :label="__('vacancies.location')" required
                                     :values="['en' => $vacancy->getTranslation('location', 'en', false), 'am' => $vacancy->getTranslation('location', 'am', false)]"
                                     :placeholder="__('vacancies.location_placeholder')" />
        </x-admin.form-section>

        {{-- 2 · Description --}}
        <x-admin.form-section number="2" :title="__('vacancies.description')" :description="__('vacancies.section_description_hint')">
            <x-admin.bilingual-field name="description" :label="__('vacancies.description')" textarea rows="8"
                                     :values="['en' => $vacancy->getTranslation('description', 'en', false), 'am' => $vacancy->getTranslation('description', 'am', false)]" />
        </x-admin.form-section>

        {{-- 3 · Eligibility --}}
        @include('admin.vacancies._requirements', ['sectionNumber' => 3])
    </div>

    {{-- ══════════════════════════════ Sidebar ══ --}}
    <aside class="space-y-6">
        <div class="space-y-6 lg:sticky lg:top-20">
            <x-admin.form-section :title="__('vacancies.section_publishing')" x-data="{ status: '{{ $currentStatus }}', help: {{ \Illuminate\Support\Js::from($statusHelp) }} }">
                <div>
                    <label class="block text-sm font-medium text-gray-700" for="status">
                        {{ __('vacancies.status') }} <span class="text-red-500">*</span>
                    </label>
                    <select id="status" name="status" x-model="status" class="form-select mt-1.5">
                        @foreach ($statuses as $s)
                        <option value="{{ $s->value }}" @selected($currentStatus === $s->value)>{{ $s->getLabel() }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1.5 text-xs leading-relaxed text-gray-500" x-text="help[status] ?? ''">{{ $statusHelp[$currentStatus] ?? '' }}</p>
                </div>

                @php $selectedAnnouncement = (string) old('announcement_id', $vacancy->announcement_id ?? ''); @endphp
                <div x-data="{ selected: @js($selectedAnnouncement) }">
                    <label for="announcement_id" class="block text-sm font-medium text-gray-700">{{ __('vacancies.announcement') }} <span class="text-red-500">*</span></label>
                    <select id="announcement_id" name="announcement_id" x-model="selected" required class="form-select mt-1.5">
                        <option value="">{{ __('vacancies.select_announcement') }}</option>
                        @foreach($announcements as $parent)
                            <option value="{{ $parent->id }}" @selected($selectedAnnouncement === (string) $parent->id)>{{ $parent->code }} — {{ $parent->subject }}</option>
                        @endforeach
                    </select>
                    @error('announcement_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    <p class="mt-2 text-xs text-gray-500">{{ __('vacancies.announcement_dates_hint') }}</p>
                    @foreach($announcements as $parent)
                        <dl x-show="selected === '{{ $parent->id }}'" @if($selectedAnnouncement !== (string) $parent->id) style="display: none" @endif class="mt-3 space-y-2 rounded-lg bg-gray-50 p-3 text-sm">
                            <div><dt class="text-gray-500">{{ __('vacancies.opening_date') }}</dt><dd class="font-semibold">{{ et_date($parent->opening_date, 'M d, Y') }}</dd></div>
                            <div><dt class="text-gray-500">{{ __('vacancies.closing_date') }}</dt><dd class="font-semibold">{{ et_date($parent->closing_date, 'M d, Y') }}</dd></div>
                            <div><dt class="text-gray-500">{{ __('vacancies.announcement_institutions') }}</dt><dd>{{ $parent->institutions->pluck('name')->join(', ') ?: '—' }}</dd></div>
                        </dl>
                    @endforeach
                    @can('vacancies.create')
                        <a href="{{ route('admin.announcements.create') }}" class="mt-3 inline-block text-sm text-brand">{{ __('vacancies.create_announcement') }}</a>
                    @endcan
                </div>

                @if($isEdit && $vacancy->published_at)
                <p class="text-xs text-gray-500">{{ __('vacancies.published_on', ['date' => et_date($vacancy->published_at, 'd M Y')]) }}</p>
                @endif

                <div class="flex flex-col gap-2 border-t border-gray-100 pt-4">
                    <button type="submit" class="btn btn-primary w-full justify-center py-2.5">
                        {{ $isEdit ? __('messages.save_changes') : __('vacancies.create_vacancy') }}
                    </button>
                    <a href="{{ $isEdit ? route('admin.vacancies.show', $vacancy) : route('admin.vacancies.index') }}" class="btn btn-secondary w-full justify-center">
                        {{ __('messages.cancel') }}
                    </a>
                </div>
            </x-admin.form-section>

            <div class="rounded-2xl border border-dashed border-gray-300 bg-gray-50 p-4 text-xs leading-relaxed text-gray-500">
                <p class="mb-1 font-semibold text-gray-700">{{ __('vacancies.form_tip_title') }}</p>
                {{ __('vacancies.form_tip_body') }}
            </div>
        </div>
    </aside>
</div>
