{{--
    One English/Amharic field pair shown as a single input with language tabs.
    Submits the usual `name[en]` and `name[am]` keys.
--}}
@props([
    'name',
    'label',
    'values' => [],        // ['en' => ..., 'am' => ...]
    'required' => false,   // English required
    'textarea' => false,
    'rows' => 5,
    'placeholder' => null,
    'hint' => null,
])

@php
    $en = old("{$name}.en", $values['en'] ?? '');
    $am = old("{$name}.am", $values['am'] ?? '');
    $hasError = $errors->has("{$name}.en") || $errors->has("{$name}.am") || $errors->has($name);
    $inputClass = ($textarea ? 'form-textarea' : 'form-input').($hasError ? ' form-input-error' : '');
    $id = 'bf_'.str_replace(['[', ']', '.'], '_', $name);
@endphp

<div x-data="{ lang: 'en', en: @js((string) $en), am: @js((string) $am) }">
    <div class="flex items-end justify-between gap-3">
        <label :for="'{{ $id }}_' + lang" class="block text-sm font-medium text-gray-700">
            {{ $label }}
            @if($required)<span class="text-red-500">*</span>@endif
        </label>
        <div class="inline-flex rounded-lg bg-gray-100 p-0.5 text-xs font-semibold" role="tablist" aria-label="{{ $label }}">
            @foreach(['en' => 'EN', 'am' => 'አማ'] as $code => $tab)
            <button type="button" role="tab" @click="lang = '{{ $code }}'"
                    :aria-selected="(lang === '{{ $code }}').toString()"
                    :class="lang === '{{ $code }}' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-800'"
                    class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 transition">
                {{ $tab }}
                <span class="h-1.5 w-1.5 rounded-full"
                      :class="{{ $code }}.trim() ? 'bg-green-500' : '{{ $code === 'en' && $required ? 'bg-red-400' : 'bg-gray-300' }}'"
                      :title="{{ $code }}.trim() ? @js(__('vacancies.translation_filled')) : @js(__('vacancies.translation_missing'))"></span>
            </button>
            @endforeach
        </div>
    </div>

    @foreach(['en', 'am'] as $code)
        @if($textarea)
        {{-- No HTML `required`: a hidden tab's input can't be focused, which silently blocks submit. --}}
        <textarea id="{{ $id }}_{{ $code }}" name="{{ $name }}[{{ $code }}]" rows="{{ $rows }}" x-model="{{ $code }}"
                  x-show="lang === '{{ $code }}'" @if($code === 'am') x-cloak lang="am" @endif
                  placeholder="{{ $placeholder }}"
                  class="{{ $inputClass }} mt-1.5">{{ $code === 'en' ? $en : $am }}</textarea>
        @else
        <input type="text" id="{{ $id }}_{{ $code }}" name="{{ $name }}[{{ $code }}]" x-model="{{ $code }}"
               value="{{ $code === 'en' ? $en : $am }}"
               x-show="lang === '{{ $code }}'" @if($code === 'am') x-cloak lang="am" @endif
               placeholder="{{ $placeholder }}"
               class="{{ $inputClass }} mt-1.5">
        @endif
    @endforeach

    @if($hint)
    <p class="mt-1 text-xs text-gray-500">{{ $hint }}</p>
    @endif
    @foreach(["{$name}.en", "{$name}.am", $name] as $key)
        @error($key)<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    @endforeach
</div>
