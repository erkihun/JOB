@php
    $isEdit = isset($announcement);
    $isAm = app()->getLocale() === 'am';
    // Past "published" the stage moves only through the lifecycle actions on the announcement page.
    $lifecycleLocked = $isEdit && ! in_array($announcement->status, ['draft', 'published'], true);
    $currentStatus = old('status', $announcement->status ?? 'draft');
    $existingPublishedAt = $isEdit ? $announcement->published_at : null;

    // Publishing mode: draft | now | schedule (schedule = published with a future date)
    $oldPublishedAt = old('published_at', old('_pub_date'));
    $publishMode = match (true) {
        $currentStatus !== 'published' => 'draft',
        filled($oldPublishedAt) && \Illuminate\Support\Carbon::parse($oldPublishedAt)->isFuture() => 'schedule',
        $existingPublishedAt !== null && $existingPublishedAt->isFuture() && ! old('status') => 'schedule',
        default => 'now',
    };
    $scheduleValue = old('published_at', $existingPublishedAt?->isFuture() ? $existingPublishedAt->format('Y-m-d\TH:i') : '');
    $pubDate = old('_pub_date', $existingPublishedAt?->isFuture() ? $existingPublishedAt->format('Y-m-d') : '');
    $pubTime = old('_pub_time', $existingPublishedAt?->isFuture() ? $existingPublishedAt->format('H:i') : '09:00');

    $selectedInstitutions = array_map('strval', (array) old('institution_ids', $isEdit ? $announcement->institutions->modelKeys() : []));
    $opening = old('opening_date', $isEdit ? $announcement->opening_date?->format('Y-m-d') : '');
    $closing = old('closing_date', $isEdit ? $announcement->closing_date?->format('Y-m-d') : '');
@endphp

<div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start"
     x-data="{
        mode: @js($publishMode),
        opening: @js($opening), closing: @js($closing),
        instSearch: '',
        selectedCount: {{ count($selectedInstitutions) }},
        readDates() {
            this.opening = this.$root.querySelector('[name=opening_date]')?.value || '';
            this.closing = this.$root.querySelector('[name=closing_date]')?.value || '';
        },
        get days() {
            if (!this.opening || !this.closing) return null;
            return Math.round((new Date(this.closing) - new Date(this.opening)) / 86400000);
        },
     }"
     @change="readDates()" @input="readDates()">

    {{-- ════════════ Main column ════════════ --}}
    <div class="space-y-6">
        @if($errors->any())
        <div class="alert alert-danger" role="alert">
            <p class="font-semibold">{{ __('applicant.fix_errors_heading') }}</p>
            <ul class="mt-1 list-inside list-disc text-sm">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
        @endif

        <section class="card" aria-labelledby="ann-details">
            <div class="border-b border-gray-100 px-5 py-4 sm:px-6">
                <h2 id="ann-details" class="card-title">{{ __('messages.ann_details') }}</h2>
                <p class="mt-0.5 text-sm text-gray-600">{{ __('messages.ann_details_hint') }}</p>
            </div>
            <div class="space-y-5 p-5 sm:p-6">
                <div>
                    <label for="subject" class="form-label">{{ __('messages.ann_title') }} <span class="form-required">*</span></label>
                    <input type="text" id="subject" name="subject" required maxlength="255"
                           value="{{ old('subject', $announcement->subject ?? '') }}"
                           placeholder="{{ __('messages.ann_title_placeholder') }}"
                           class="form-input text-base @error('subject') form-input-error @enderror">
                    @error('subject')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="max-w-sm">
                    <label for="code" class="form-label">{{ __('vacancies.announcement_code') }} <span class="form-required">*</span></label>
                    <input id="code" name="code" required maxlength="100" value="{{ old('code', $announcement->code ?? '') }}"
                           placeholder="ANN-{{ now()->year }}-01" autocomplete="off" spellcheck="false"
                           class="form-input font-mono uppercase @error('code') form-input-error @enderror">
                    <p class="form-hint">{{ __('messages.ann_code_hint') }}</p>
                    @error('code')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="tinymce-content" class="form-label">{{ __('messages.ann_content') }}</label>
                    <p class="form-hint mb-2 mt-0">{{ __('messages.ann_content_hint') }}</p>
                    <textarea name="content" id="tinymce-content" rows="14"
                              class="form-textarea @error('content') form-input-error @enderror">{{ old('content', $announcement->content ?? '') }}</textarea>
                    @error('content')<p class="form-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        @if($isEdit)
            @include('admin.announcements._vacancies')
        @else
            <div class="flex items-start gap-3 rounded-xl border border-dashed border-gray-300 bg-white px-5 py-4 text-sm text-gray-600">
                <svg class="mt-0.5 h-5 w-5 shrink-0 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                <p>{{ __('vacancies.save_announcement_first') }}</p>
            </div>
        @endif
    </div>

    {{-- ════════════ Sidebar ════════════ --}}
    <aside class="space-y-6 lg:sticky lg:top-24">

        {{-- Publishing --}}
        <section class="card" aria-labelledby="ann-publish">
            <h2 id="ann-publish" class="card-title border-b border-gray-100 px-5 py-4">{{ __('messages.ann_publishing') }}</h2>
            <div class="space-y-3 p-5">
                @if($lifecycleLocked)
                <input type="hidden" name="status" value="published">
                <p class="flex items-center justify-between gap-3 text-sm">
                    <span class="text-gray-600">{{ __('recruitment.current_stage') }}</span>
                    <x-admin.status :tone="$announcement->stage()->tone()" :label="$announcement->stage()->label()" />
                </p>
                <a href="{{ route('admin.announcements.show', $announcement) }}" class="btn btn-secondary btn-sm w-full justify-center">{{ __('recruitment.lifecycle') }}</a>
                @else
                <input type="hidden" name="status" :value="mode === 'draft' ? 'draft' : 'published'" value="{{ $currentStatus }}">
                @foreach([
                    'draft'    => [__('messages.draft'), __('messages.ann_mode_draft_hint'), 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'],
                    'now'      => [$existingPublishedAt && $existingPublishedAt->isPast() ? __('messages.published') : __('messages.ann_mode_now'), $existingPublishedAt && $existingPublishedAt->isPast() ? __('messages.ann_mode_live_since', ['date' => et_date($existingPublishedAt)]) : __('messages.ann_mode_now_hint'), 'M5 13l4 4L19 7'],
                    'schedule' => [__('messages.ann_mode_schedule'), __('messages.ann_mode_schedule_hint'), 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                ] as $value => [$label, $hint, $icon])
                <label class="flex cursor-pointer items-start gap-3 rounded-xl border p-3 transition"
                       :class="mode === '{{ $value }}' ? 'border-brand bg-brand-muted/60 ring-1 ring-brand' : 'border-gray-200 hover:border-gray-300'">
                    <input type="radio" name="_publish_mode" value="{{ $value }}" x-model="mode" class="sr-only">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                          :class="mode === '{{ $value }}' ? 'bg-brand text-white' : 'bg-gray-100 text-gray-500'">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                    </span>
                    <span>
                        <span class="block text-sm font-semibold text-gray-900">{{ $label }}</span>
                        <span class="block text-xs leading-snug text-gray-600">{{ $hint }}</span>
                    </span>
                </label>
                @endforeach
                @error('status')<p class="form-error">{{ $message }}</p>@enderror

                {{-- Schedule date: only submitted in schedule mode --}}
                <div x-show="mode === 'schedule'" x-cloak class="rounded-xl bg-gray-50 p-3">
                    @if($isAm)
                        {{-- Always rendered; the server ignores it unless "schedule" is chosen. --}}
                        <div class="space-y-3">
                            <x-ethiopian-datepicker name="_pub_date" :label="__('messages.ann_publish_on')" :value="$pubDate"/>
                            <div>
                                <label for="_pub_time" class="form-label">{{ __('dashboard.table.time') }}</label>
                                <input type="time" id="_pub_time" name="_pub_time" value="{{ $pubTime }}" class="form-input">
                            </div>
                        </div>
                    @else
                        <label for="published_at" class="form-label">{{ __('messages.ann_publish_on') }}</label>
                        <input type="datetime-local" id="published_at" name="published_at" :disabled="mode !== 'schedule'"
                               min="{{ now()->format('Y-m-d\TH:i') }}" value="{{ $scheduleValue }}"
                               class="form-input @error('published_at') form-input-error @enderror">
                    @endif
                    @error('published_at')<p class="form-error">{{ $message }}</p>@enderror
                </div>
                @endif
            </div>
        </section>

        {{-- Application period --}}
        <section class="card" aria-labelledby="ann-period">
            <div class="border-b border-gray-100 px-5 py-4">
                <h2 id="ann-period" class="card-title">{{ __('messages.ann_period') }}</h2>
                <p class="mt-0.5 text-xs text-gray-600">{{ __('messages.ann_period_hint') }}</p>
            </div>
            <div class="space-y-4 p-5">
                @if($datesLocked ?? false)
                    {{-- Published: the period is read-only; it only moves via "Extend deadline". --}}
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between gap-3"><dt class="text-gray-600">{{ __('vacancies.opening_date') }}</dt><dd class="font-semibold text-gray-900">{{ et_date($announcement->opening_date, 'M d, Y') }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-gray-600">{{ __('vacancies.closing_date') }}</dt><dd class="font-semibold text-gray-900">{{ et_date($announcement->closing_date, 'M d, Y') }}</dd></div>
                    </dl>
                    <p class="rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-700">{{ __('recruitment.dates_locked_hint') }}</p>
                    @error('opening_date')<p class="form-error">{{ $message }}</p>@enderror
                    @error('closing_date')<p class="form-error">{{ $message }}</p>@enderror
                @else
                @foreach(['opening_date', 'closing_date'] as $dateField)
                    @if($isAm)
                        <x-ethiopian-datepicker :name="$dateField" :label="__('vacancies.'.$dateField)" :value="$dateField === 'opening_date' ? $opening : $closing" required />
                    @else
                        <div>
                            <label for="{{ $dateField }}" class="form-label">{{ __('vacancies.'.$dateField) }} <span class="form-required">*</span></label>
                            <input type="date" id="{{ $dateField }}" name="{{ $dateField }}" required
                                   value="{{ $dateField === 'opening_date' ? $opening : $closing }}"
                                   @if($dateField === 'closing_date') :min="opening" @endif
                                   class="form-input @error($dateField) form-input-error @enderror">
                        </div>
                    @endif
                    @error($dateField)<p class="form-error">{{ $message }}</p>@enderror
                @endforeach

                <p x-show="days !== null && days > 0" x-cloak class="flex items-center gap-2 rounded-lg bg-brand-muted px-3 py-2 text-sm font-semibold text-brand-dark">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span x-text="@js(__('messages.ann_open_for')).replace(':days', days)"></span>
                </p>
                <p x-show="days !== null && days <= 0" x-cloak class="rounded-lg bg-red-50 px-3 py-2 text-sm font-semibold text-red-700" role="alert">
                    {{ __('messages.ann_closing_before_opening') }}
                </p>
                @endif

                @php $examLocked = $isEdit && $announcement->lifecycleStatus()->hasStartedAssessment(); @endphp
                <div class="border-t border-gray-100 pt-4">
                    <input type="hidden" name="exam_required" value="0" @disabled($examLocked)>
                    <label class="flex items-start gap-3 text-sm">
                        <input type="checkbox" name="exam_required" value="1" @checked(old('exam_required', $announcement->exam_required ?? true)) @disabled($examLocked)
                               class="mt-0.5 h-4 w-4 rounded border-gray-300 text-brand focus:ring-brand">
                        <span>
                            <span class="block font-semibold text-gray-900">{{ __('recruitment.exam_required') }}</span>
                            <span class="block text-xs text-gray-600">{{ __('recruitment.exam_required_hint') }}</span>
                        </span>
                    </label>
                </div>
            </div>
        </section>

        {{-- Institutions --}}
        <section class="card" aria-labelledby="ann-inst">
            <div class="flex items-start justify-between gap-3 border-b border-gray-100 px-5 py-4">
                <div>
                    <h2 id="ann-inst" class="card-title">{{ __('vacancies.announcement_institutions') }}</h2>
                    <p class="mt-0.5 text-xs text-gray-600">{{ __('messages.ann_institutions_hint') }}</p>
                </div>
                <span class="badge badge-gray shrink-0" x-text="selectedCount + ' ' + @js(__('messages.ann_selected'))"></span>
            </div>
            <div class="p-5">
                @if($institutions->isEmpty())
                    <p class="text-sm text-gray-600">{{ __('messages.no_records') }}</p>
                @else
                    @if($institutions->count() > 6)
                    <input type="search" x-model="instSearch" placeholder="{{ __('messages.search') }}…" aria-label="{{ __('messages.search') }}"
                           class="form-input mb-3">
                    @endif
                    <ul class="max-h-64 space-y-1 overflow-y-auto pr-1"
                        @change="selectedCount = $el.querySelectorAll('input:checked').length">
                        @foreach($institutions as $institution)
                        <li x-show="!instSearch || @js(mb_strtolower($institution->name.' '.($institution->short_name ?? ''))).includes(instSearch.toLowerCase())">
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg px-2 py-2 text-sm hover:bg-gray-50">
                                <input type="checkbox" name="institution_ids[]" value="{{ $institution->id }}"
                                       @checked(in_array((string) $institution->id, $selectedInstitutions, true))
                                       class="h-4 w-4 rounded border-gray-300 text-brand focus:ring-brand">
                                <span class="min-w-0 flex-1 truncate text-gray-800">{{ $institution->name }}</span>
                            </label>
                        </li>
                        @endforeach
                    </ul>
                @endif
                @error('institution_ids')<p class="form-error">{{ $message }}</p>@enderror
                @error('institution_ids.*')<p class="form-error">{{ $message }}</p>@enderror
            </div>
        </section>

        {{-- Actions --}}
        <div class="card card-body space-y-2">
            <button type="submit" class="btn btn-primary w-full justify-center">
                <span x-show="mode === 'draft'">{{ $isEdit ? __('messages.save_changes') : __('messages.ann_save_draft') }}</span>
                <span x-show="mode === 'now'" x-cloak>{{ $isEdit ? __('messages.save_changes') : __('messages.ann_publish') }}</span>
                <span x-show="mode === 'schedule'" x-cloak>{{ __('messages.ann_schedule') }}</span>
            </button>
            <a href="{{ route('admin.announcements.index') }}" class="btn btn-secondary w-full justify-center">{{ __('messages.cancel') }}</a>
        </div>
    </aside>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
tinymce.init({
    selector: '#tinymce-content',
    height: 460,
    menubar: false,
    plugins: [
        'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
        'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
        'insertdatetime', 'media', 'table', 'wordcount'
    ],
    toolbar:
        'undo redo | blocks | bold italic underline | ' +
        'bullist numlist | alignleft aligncenter alignright | ' +
        'link table | removeformat | preview code fullscreen',
    block_formats: 'Paragraph=p; Heading=h2; Subheading=h3',
    // Same typefaces as the public page: Abyssinica SIL for Amharic, sans for Latin.
    content_style: "@font-face { font-family: 'Abyssinica SIL'; src: url('{{ asset('fonts/abyssinica/AbyssinicaSIL-Regular.woff2') }}') format('woff2'); font-weight: 400 800; unicode-range: U+1200-137F, U+1380-139F, U+2D80-2DDF, U+AB00-AB2F; } "
        + "body { font-family: 'Abyssinica SIL', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 15px; line-height: 1.65; max-width: 760px; margin: 16px auto; padding: 0 12px; }",
    branding: false,
    promotion: false,
    // Sync editor content back to the textarea as the user types.
    setup: function (editor) {
        editor.on('change input undo redo', function () { editor.save(); });
    },
});

// Flush TinyMCE into the textarea when *this* form submits (the page has other forms,
// e.g. the top-bar search, so never use document.querySelector('form')).
document.getElementById('tinymce-content')?.form?.addEventListener('submit', function () {
    if (typeof tinymce !== 'undefined') {
        tinymce.triggerSave();
    }
});
</script>
@endpush
