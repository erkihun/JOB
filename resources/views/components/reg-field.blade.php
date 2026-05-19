@props([
    'name',
    'label',
    'type'        => 'text',
    'required'    => false,
    'placeholder' => null,
    'value'       => null,
    'class'       => '',
])

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
        {{ $required ? 'required' : '' }}
        class="mt-1 w-full rounded-md border px-3 py-2 text-sm shadow-sm transition focus:outline-none focus:ring-1 focus:ring-blue-500
               {{ $errors->has($name) ? 'border-red-400 bg-red-50' : 'border-gray-300 bg-white' }}"
    >
    @error($name)
        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
