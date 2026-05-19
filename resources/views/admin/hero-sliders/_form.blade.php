@php $isEdit = isset($heroSlider); @endphp

<div class="space-y-6 rounded-xl border border-gray-100 bg-white p-6 shadow-sm">

    {{-- Image upload --}}
    <div x-data="{
        preview: '{{ $isEdit && $heroSlider->image_path ? Storage::url($heroSlider->image_path) : '' }}',
        onChange(e) { const f = e.target.files[0]; if (f) this.preview = URL.createObjectURL(f); }
    }">
        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('messages.image') }}</label>
        <div class="flex items-start gap-5">
            <div class="shrink-0">
                <template x-if="preview">
                    <img :src="preview" class="h-28 w-48 rounded-lg object-cover border border-gray-200">
                </template>
                <template x-if="!preview">
                    <div class="flex h-28 w-48 items-center justify-center rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 text-gray-400">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                </template>
            </div>
            <div>
                <input type="file" name="image" accept="image/jpeg,image/png,image/webp"
                       @change="onChange($event)"
                       class="block text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-brand-muted file:text-brand hover:file:bg-brand/10 cursor-pointer">
                <p class="mt-1 text-xs text-gray-400">JPG, PNG, WebP · max 3 MB. Recommended: 1400 × 600 px.</p>
                @error('image')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    <hr class="border-gray-100">

    {{-- Titles --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('messages.title') }} (EN) <span class="text-red-500">*</span></label>
            <input type="text" name="title_en"
                   value="{{ old('title_en', $isEdit ? $heroSlider->getTranslation('title', 'en', false) : '') }}"
                   class="form-input mt-1 @error('title_en') form-input-error @enderror">
            @error('title_en')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('messages.title') }} (አማ)</label>
            <input type="text" name="title_am"
                   value="{{ old('title_am', $isEdit ? $heroSlider->getTranslation('title', 'am', false) : '') }}"
                   class="form-input mt-1">
        </div>
    </div>

    {{-- Subtitles --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('messages.subtitle') }} (EN)</label>
            <textarea name="subtitle_en" rows="2"
                      class="form-input mt-1">{{ old('subtitle_en', $isEdit ? $heroSlider->getTranslation('subtitle', 'en', false) : '') }}</textarea>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('messages.subtitle') }} (አማ)</label>
            <textarea name="subtitle_am" rows="2"
                      class="form-input mt-1">{{ old('subtitle_am', $isEdit ? $heroSlider->getTranslation('subtitle', 'am', false) : '') }}</textarea>
        </div>
    </div>

    <hr class="border-gray-100">

    {{-- Button --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('messages.button_text') }} (EN)</label>
            <input type="text" name="button_text_en"
                   value="{{ old('button_text_en', $isEdit ? $heroSlider->getTranslation('button_text', 'en', false) : '') }}"
                   class="form-input mt-1" placeholder="e.g. Browse Vacancies">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('messages.button_text') }} (አማ)</label>
            <input type="text" name="button_text_am"
                   value="{{ old('button_text_am', $isEdit ? $heroSlider->getTranslation('button_text', 'am', false) : '') }}"
                   class="form-input mt-1">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('messages.button_link') }}</label>
            <input type="text" name="button_link"
                   value="{{ old('button_link', $isEdit ? $heroSlider->button_link : '') }}"
                   class="form-input mt-1" placeholder="https://example.com/vacancies">
        </div>
    </div>

    <hr class="border-gray-100">

    {{-- Sort order + Active --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('messages.order') }}</label>
            <input type="number" name="sort_order" min="0"
                   value="{{ old('sort_order', $isEdit ? $heroSlider->sort_order : 0) }}"
                   class="form-input mt-1">
            <p class="mt-1 text-xs text-gray-400">Lower number shows first.</p>
        </div>
        <div class="flex items-center gap-3 pt-7">
            <input type="checkbox" name="is_active" id="is_active" value="1"
                   {{ old('is_active', $isEdit ? $heroSlider->is_active : true) ? 'checked' : '' }}
                   class="h-4 w-4 rounded border-gray-300 text-brand focus:ring-brand">
            <label for="is_active" class="text-sm font-medium text-gray-700">{{ __('messages.active') }}</label>
        </div>
    </div>

    <div class="flex gap-3 pt-2">
        <button type="submit" class="btn btn-primary">
            {{ $isEdit ? __('messages.save_changes') : __('messages.create') }}
        </button>
        <a href="{{ route('admin.hero-sliders.index') }}" class="btn btn-secondary">{{ __('messages.cancel') }}</a>
    </div>
</div>
