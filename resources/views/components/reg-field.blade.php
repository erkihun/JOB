@props([
    'name',
    'label',
    'type'        => 'text',
    'required'    => false,
    'placeholder' => null,
    'value'       => null,
    'class'       => '',
    'hint'        => null,
])

@php $hasServerError = $errors->has($name); @endphp

<div class="{{ $class }}">
    <label for="{{ $name }}" class="block text-sm font-medium text-gray-700">
        {!! $label !!}
        @if($required) <span class="text-red-500">*</span> @endif
    </label>
    <input
        type="{{ $type }}"
        id="{{ $name }}"
        name="{{ $name }}"
        value="{{ old($name, $value) }}"
        @if($placeholder) placeholder="{{ $placeholder }}" @endif
        {{ $attributes->except('class') }}
        {{ $required ? 'required' : '' }}
        aria-describedby="{{ $name }}-error{{ $hint ? ' '.$name.'-hint' : '' }}"
        :aria-invalid="(touched['{{ $name }}'] ? !!fieldErrors['{{ $name }}'] : {{ $hasServerError ? 'true' : 'false' }}) ? 'true' : 'false'"
        @blur="validateField('{{ $name }}', $event.target.value)"
        @unless($attributes->has('@input'))
            @input="if (touched['{{ $name }}']) validateField('{{ $name }}', $event.target.value)"
        @endunless
        :class="(touched['{{ $name }}'] ? !!fieldErrors['{{ $name }}'] : {{ $hasServerError ? 'true' : 'false' }})
            ? 'border-red-400 bg-red-50' : 'border-gray-300 bg-white'"
        class="mt-1 w-full rounded-md border px-3 py-2 text-sm shadow-sm transition focus:outline-none focus:ring-1 focus:ring-blue-500"
    >
    @if($hint)
    <p id="{{ $name }}-hint" class="mt-1 text-xs text-gray-400">{{ $hint }}</p>
    @endif
    <p id="{{ $name }}-error">
        @if($hasServerError)
        <span x-show="!touched['{{ $name }}']" class="mt-1 block text-xs text-red-600">{{ $errors->first($name) }}</span>
        @endif
        <span x-show="touched['{{ $name }}'] && !!fieldErrors['{{ $name }}']"
           x-text="fieldErrors['{{ $name }}'] || ''"
           class="mt-1 block text-xs text-red-600"></span>
    </p>
</div>
