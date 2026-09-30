@extends('layouts.admin')
@section('title', $type->getLabel())
@section('content')
@php
    $localeNames = ['en' => 'English', 'am' => 'አማርኛ'];
    $orgName = \App\Models\Setting::get('org.name', config('app.name'));
    $sample = [
        'applicant_name'      => __('messages.ntpl_sample.applicant_name'),
        'vacancy_title'       => __('messages.ntpl_sample.vacancy_title'),
        'reference_number'    => 'APP-2026-000123',
        'contact_information' => \App\Models\Setting::get('org.email', config('mail.from.address', 'hr@example.gov.et')),
        'date'                => now()->addDays(14)->format('Y-m-d'),
        'time'                => '09:00',
        'venue'               => __('messages.ntpl_sample.venue'),
        'instructions'        => __('messages.ntpl_sample.instructions'),
        'remark'              => __('messages.ntpl_sample.remark'),
        'message'             => __('messages.ntpl_sample.message'),
    ];
    $sample = array_intersect_key($sample, array_flip($placeholders));
    $isCustom = $template !== null;
@endphp
<div class="space-y-6"
     x-data="templateEditor(@js(old('subject', $subject)), @js(old('body', $body)), @js((bool) old('active', $active)), @js($placeholders), @js($sample))">

    <x-admin.page-header :title="$type->getLabel()"
                         :description="__('messages.ntpl_when.'.$type->value)"
                         :crumbs="[['label' => __('menus.system')], ['label' => __('menus.notification_templates'), 'url' => route('admin.notification-templates.index')], ['label' => $type->getLabel()]]">
        {{-- Language switch --}}
        @if(count($locales) > 1)
        <nav class="flex rounded-lg border border-gray-200 bg-white p-0.5 text-sm font-semibold" aria-label="{{ __('messages.locale') }}">
            @foreach($locales as $code)
            <a href="{{ route('admin.notification-templates.edit', [$type->value, $code]) }}"
               @if($code === $locale) aria-current="page" @endif
               class="rounded-md px-3 py-1.5 {{ $code === $locale ? 'bg-brand text-white' : 'text-gray-700 hover:bg-gray-100' }}">{{ $localeNames[$code] ?? strtoupper($code) }}</a>
            @endforeach
        </nav>
        @endif
    </x-admin.page-header>

    {{-- State banner --}}
    <div class="flex items-start gap-3 rounded-xl border px-4 py-3 text-sm {{ $isCustom ? ($template->active ? 'border-green-200 bg-green-50 text-green-900' : 'border-amber-200 bg-amber-50 text-amber-900') : 'border-gray-200 bg-gray-50 text-gray-800' }}">
        <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <p>
            @if(! $isCustom)
                {{ __('messages.ntpl_banner_default') }}
            @elseif($template->active)
                {{ __('messages.ntpl_banner_custom', ['date' => et_date($template->updated_at)]) }}
            @else
                {{ __('messages.ntpl_banner_off') }}
            @endif
        </p>
    </div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_24rem] lg:items-start">

        {{-- ── Editor ── --}}
        <form method="POST" action="{{ route('admin.notification-templates.update', [$type->value, $locale]) }}" class="card overflow-hidden">
            @csrf @method('PUT')
            <div class="space-y-5 p-5 sm:p-6">
                <div>
                    <label for="subject" class="form-label">{{ __('messages.subject') }} <span class="form-required">*</span></label>
                    <input type="text" id="subject" name="subject" x-model="subject" x-ref="subject" @focus="field = 'subject'" maxlength="255" required
                           class="form-input @error('subject') form-input-error @enderror">
                    @error('subject')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <p class="form-label">{{ __('messages.ntpl_placeholders') }}</p>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="key in allowed" :key="key">
                            <button type="button" @click="insert(key)"
                                    class="inline-flex items-center gap-1.5 rounded-md border border-brand/25 bg-brand-muted px-2.5 py-1 font-mono text-xs font-semibold text-brand-dark transition hover:border-brand hover:bg-brand/15"
                                    :title="labels[key]">
                                <span x-text="token(key)"></span>
                            </button>
                        </template>
                    </div>
                    <p class="form-hint mt-1.5">{{ __('messages.ntpl_placeholders_hint') }}</p>
                </div>

                <div>
                    <div class="mb-1.5 flex items-center justify-between gap-3">
                        <label for="body" class="form-label mb-0">{{ __('messages.body') }} <span class="form-required">*</span></label>
                        <span class="text-xs text-gray-500"><span x-text="body.length"></span> / 5000</span>
                    </div>
                    <textarea id="body" name="body" rows="14" x-model="body" x-ref="body" @focus="field = 'body'" maxlength="5000" required
                              class="form-textarea leading-relaxed @error('body') form-input-error @enderror"></textarea>
                    @error('body')<p class="form-error">{{ $message }}</p>@enderror

                    <div x-show="unknown.length" x-cloak class="mt-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900" role="status">
                        {{ __('messages.ntpl_unknown') }}
                        <span class="font-mono font-semibold" x-text="unknown.map(k => token(k)).join(', ')"></span>
                    </div>
                </div>

                {{-- Active switch --}}
                <div class="flex items-start justify-between gap-4 rounded-xl border border-gray-200 p-4">
                    <div>
                        <p class="text-sm font-semibold text-gray-900">{{ __('messages.ntpl_use_custom') }}</p>
                        <p class="mt-0.5 text-sm text-gray-600">{{ __('messages.ntpl_use_custom_hint') }}</p>
                    </div>
                    <input type="hidden" name="active" value="0">
                    <button type="button" role="switch" :aria-checked="active.toString()" @click="active = !active"
                            aria-label="{{ __('messages.ntpl_use_custom') }}"
                            class="relative mt-0.5 inline-flex h-6 w-11 shrink-0 items-center rounded-full transition"
                            :class="active ? 'bg-brand' : 'bg-gray-300'">
                        <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition" :class="active ? 'translate-x-5' : 'translate-x-0.5'"></span>
                    </button>
                    <input type="checkbox" name="active" value="1" class="hidden" :checked="active">
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 bg-gray-50 px-5 py-4 sm:px-6">
                <div class="flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary">{{ $isCustom ? __('messages.save_changes') : __('messages.ntpl_save_custom') }}</button>
                    <a href="{{ route('admin.notification-templates.index') }}" class="btn btn-secondary">{{ __('messages.cancel') }}</a>
                </div>
                <button type="button" x-show="subject !== defaults.subject || body !== defaults.body" x-cloak
                        @click="subject = defaults.subject; body = defaults.body"
                        class="text-sm font-semibold text-gray-600 hover:text-gray-900 hover:underline">
                    {{ __('messages.ntpl_load_default') }}
                </button>
            </div>
        </form>

        {{-- ── Preview ── --}}
        <aside class="space-y-4 lg:sticky lg:top-24">
            <div class="card overflow-hidden">
                <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                    <h2 class="card-title">{{ __('messages.ntpl_preview') }}</h2>
                    <span class="badge badge-gray">{{ __('messages.ntpl_sample_data') }}</span>
                </div>
                <div class="space-y-3 p-4">
                    <dl class="space-y-1 text-xs text-gray-600">
                        <div class="flex gap-2"><dt class="w-12 shrink-0">{{ __('messages.ntpl_from') }}</dt><dd class="font-semibold text-gray-800">{{ $orgName }}</dd></div>
                        <div class="flex gap-2"><dt class="w-12 shrink-0">{{ __('messages.ntpl_to') }}</dt><dd class="font-semibold text-gray-800" x-text="sample.applicant_name"></dd></div>
                    </dl>
                    <p class="border-t border-gray-100 pt-3 text-[15px] font-bold text-gray-900" x-text="render(subject) || '—'"></p>
                    <div class="whitespace-pre-line break-words text-sm leading-relaxed text-gray-800" lang="{{ $locale }}" x-text="render(body) || '—'"></div>
                </div>
            </div>

            @if($isCustom)
            <form method="POST" action="{{ route('admin.notification-templates.destroy', [$type->value, $locale]) }}"
                  class="card card-body" onsubmit="return confirm(@js(__('messages.ntpl_reset_confirm')))">
                @csrf @method('DELETE')
                <p class="text-sm font-semibold text-gray-900">{{ __('messages.ntpl_reset') }}</p>
                <p class="mt-1 text-sm text-gray-600">{{ __('messages.ntpl_reset_hint') }}</p>
                <button type="submit" class="btn btn-danger-soft btn-sm mt-3">{{ __('messages.ntpl_reset') }}</button>
            </form>
            @endif
        </aside>
    </div>
</div>

<script>
function templateEditor(subject, body, active, allowed, sample) {
    const token = (key) => '{' + '{ ' + key + ' }' + '}';
    return {
        subject, body, active, allowed, sample,
        field: 'body',
        defaults: @js($default),
        labels: @js(collect($placeholders)->mapWithKeys(fn ($k) => [$k => __('messages.ntpl_ph.'.$k)])),
        token,
        insert(key) {
            const el = this.$refs[this.field];
            const text = token(key);
            const start = el.selectionStart ?? this[this.field].length;
            const end = el.selectionEnd ?? start;
            this[this.field] = this[this.field].slice(0, start) + text + this[this.field].slice(end);
            this.$nextTick(() => { el.focus(); el.setSelectionRange(start + text.length, start + text.length); });
        },
        render(text) {
            return (text || '').replace(/\{\{\s*([a-z_]+)\s*\}\}/g, (m, key) => key in this.sample ? this.sample[key] : m);
        },
        get unknown() {
            const found = [...(this.subject + ' ' + this.body).matchAll(/\{\{\s*([a-z_]+)\s*\}\}/g)].map(m => m[1]);
            return [...new Set(found)].filter(k => !this.allowed.includes(k));
        },
    };
}
</script>
@endsection
