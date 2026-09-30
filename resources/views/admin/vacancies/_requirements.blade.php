{{--
    Eligibility requirement options.
    Options are alternatives (OR). Conditions inside one option must all be met (AND).
--}}
@php
    $blankRequirement = [
        'education_level' => '', 'field_of_study' => '', 'min_experience_years' => 0,
        'min_gpa' => '', 'graduation_year_from' => '', 'graduation_year_to' => '', 'notes' => '',
    ];

    $initialGroups = old('requirement_groups');

    if ($initialGroups === null) {
        $initialGroups = ($vacancy->exists ? $vacancy->requirementGroups : collect())
            ->map(fn ($group) => [
                'title' => $group->title ?? '',
                'is_active' => $group->is_active,
                'requirements' => $group->requirements->map(fn ($r) => [
                    'education_level' => $r->education_level?->value ?? '',
                    'field_of_study' => $r->field_of_study ?? '',
                    'min_experience_years' => $r->min_experience_years,
                    'min_gpa' => $r->min_gpa !== null ? (string) (float) $r->min_gpa : '',
                    'graduation_year_from' => $r->graduation_year_from ?? '',
                    'graduation_year_to' => $r->graduation_year_to ?? '',
                    'notes' => $r->notes ?? '',
                ])->values()->all(),
            ])->values()->all();
    } else {
        // Re-index after a failed validation so Alpine gets plain arrays.
        $initialGroups = collect($initialGroups)->map(fn ($g) => [
            'title' => $g['title'] ?? '',
            'is_active' => filter_var($g['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'requirements' => array_values(array_map(fn ($r) => array_merge($blankRequirement, (array) $r), $g['requirements'] ?? [])),
        ])->values()->all();
    }

    $requirementErrors = collect($errors->getMessages())
        ->filter(fn ($messages, $key) => str_starts_with($key, 'requirement_groups'))
        ->flatten()->unique()->values();
@endphp

<div x-data="{
        groups: @js($initialGroups),
        blank: @js($blankRequirement),
        addGroup() { this.groups.push({ title: '', is_active: true, requirements: [{ ...this.blank }] }) },
        removeGroup(i) { this.groups.splice(i, 1) },
        addRequirement(g) { g.requirements.push({ ...this.blank }) },
        removeRequirement(g, i) { g.requirements.splice(i, 1) },
     }">
<x-admin.form-section :number="$sectionNumber ?? null" :title="__('vacancies.eligibility_requirements')" :description="__('vacancies.eligibility_requirements_hint')">
    <input type="hidden" name="requirement_groups_submitted" value="1">

    @if($requirementErrors->isNotEmpty())
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-700">
        <ul class="list-inside list-disc space-y-0.5">
            @foreach($requirementErrors as $message)<li>{{ $message }}</li>@endforeach
        </ul>
    </div>
    @endif

    <div class="space-y-4">
        <template x-for="(group, gi) in groups" :key="gi">
            <div>
                {{-- OR separator between options --}}
                <div x-show="gi > 0" class="relative mb-4 flex items-center justify-center" aria-hidden="true">
                    <span class="absolute inset-x-0 h-px bg-gray-200"></span>
                    <span class="relative rounded-full bg-white px-3 text-xs font-bold uppercase tracking-widest text-accent">{{ __('vacancies.or') }}</span>
                </div>

                <div class="rounded-xl border p-4 transition" :class="group.is_active ? 'border-brand/25 bg-brand-muted/30' : 'border-gray-200 bg-gray-50 opacity-70'">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="rounded-md bg-brand px-2.5 py-1 text-xs font-bold text-white"
                              x-text="@js(__('vacancies.requirement_option', ['number' => '__N__'])).replace('__N__', gi + 1)"></span>
                        <input type="text" :name="`requirement_groups[${gi}][title]`" x-model="group.title"
                               maxlength="255" placeholder="{{ __('vacancies.option_title_placeholder') }}"
                               class="form-input min-w-0 flex-1 py-1.5 text-sm">
                        <label class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-600">
                            <input type="hidden" :name="`requirement_groups[${gi}][is_active]`" :value="group.is_active ? 1 : 0">
                            <input type="checkbox" x-model="group.is_active" class="rounded border-gray-300 text-brand focus:ring-brand">
                            {{ __('vacancies.option_active') }}
                        </label>
                        <button type="button" @click="removeGroup(gi)"
                                class="rounded-lg px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50">
                            {{ __('vacancies.remove_option') }}
                        </button>
                    </div>

                    <template x-for="(req, ri) in group.requirements" :key="ri">
                        <div class="mt-3 rounded-lg border border-gray-200 bg-white p-3">
                            <div x-show="group.requirements.length > 1" class="mb-2 flex items-center justify-between">
                                <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-600"
                                      x-text="(ri > 0 ? @js(__('vacancies.and')) + ' · ' : '') + @js(__('vacancies.condition')) + ' ' + (ri + 1)"></span>
                                <button type="button" @click="removeRequirement(group, ri)" class="text-xs text-gray-400 hover:text-red-600">
                                    {{ __('messages.delete') }}
                                </button>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600">{{ __('vacancies.education_level') }}</label>
                                    <select :name="`requirement_groups[${gi}][requirements][${ri}][education_level]`" x-model="req.education_level" class="form-select mt-1">
                                        <option value="">{{ __('vacancies.any_education_level') }}</option>
                                        @foreach($educationLevels as $level)
                                        <option value="{{ $level->value }}">{{ $level->getLabel() }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600">{{ __('vacancies.minimum_experience') }} ({{ __('vacancies.years_unit') }})</label>
                                    <input type="number" min="0" max="60" :name="`requirement_groups[${gi}][requirements][${ri}][min_experience_years]`" x-model="req.min_experience_years" class="form-input mt-1">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600">{{ __('vacancies.min_gpa') }}</label>
                                    <input type="number" min="0" max="4" step="0.01" :name="`requirement_groups[${gi}][requirements][${ri}][min_gpa]`" x-model="req.min_gpa" class="form-input mt-1" placeholder="—">
                                </div>
                                <div class="sm:col-span-2 lg:col-span-3">
                                    <label class="block text-xs font-medium text-gray-600">{{ __('vacancies.field_of_study') }}</label>
                                    <input type="text" maxlength="255" :name="`requirement_groups[${gi}][requirements][${ri}][field_of_study]`" x-model="req.field_of_study" class="form-input mt-1"
                                           placeholder="{{ __('vacancies.field_of_study_placeholder') }}">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600">{{ __('vacancies.graduation_year_from') }}</label>
                                    <input type="number" min="1950" max="2100" :name="`requirement_groups[${gi}][requirements][${ri}][graduation_year_from]`" x-model="req.graduation_year_from" class="form-input mt-1" placeholder="—">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600">{{ __('vacancies.graduation_year_to') }}</label>
                                    <input type="number" min="1950" max="2100" :name="`requirement_groups[${gi}][requirements][${ri}][graduation_year_to]`" x-model="req.graduation_year_to" class="form-input mt-1" placeholder="—">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600">{{ __('vacancies.requirement_notes') }}</label>
                                    <input type="text" maxlength="1000" :name="`requirement_groups[${gi}][requirements][${ri}][notes]`" x-model="req.notes" class="form-input mt-1">
                                </div>
                            </div>
                        </div>
                    </template>

                    <button type="button" @click="addRequirement(group)"
                            class="mt-3 text-xs font-semibold text-brand hover:underline">
                        + {{ __('vacancies.add_condition') }}
                    </button>
                </div>
            </div>
        </template>

        <p x-show="groups.length === 0" class="rounded-lg border border-dashed border-gray-300 px-4 py-6 text-center text-sm text-gray-500">
            {{ __('vacancies.no_requirement_options') }}
        </p>

        <button type="button" @click="addGroup()"
                class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-dashed border-brand/40 px-4 py-2.5 text-sm font-semibold text-brand transition hover:bg-brand-muted">
            + {{ __('vacancies.add_requirement_option') }}
        </button>
    </div>

    {{-- Free-text requirements that can't be expressed as structured rules --}}
    <div class="border-t border-gray-100 pt-5">
        <x-admin.bilingual-field name="qualification_requirements" :label="__('vacancies.other_requirements')" textarea rows="4"
                                 :values="['en' => $vacancy->getTranslation('qualification_requirements', 'en', false), 'am' => $vacancy->getTranslation('qualification_requirements', 'am', false)]"
                                 :placeholder="__('vacancies.other_requirements_placeholder')"
                                 :hint="__('vacancies.other_requirements_hint')" />
    </div>
</x-admin.form-section>
</div>
