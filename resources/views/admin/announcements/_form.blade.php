@php $isEdit = isset($announcement); @endphp

<div class="space-y-5 rounded-xl border border-gray-100 bg-white p-6 shadow-sm">

    {{-- Subject --}}
    <div>
        <label class="block text-sm font-medium text-gray-700">{{ __('messages.subject') }} <span class="text-red-500">*</span></label>
        <input type="text" name="subject"
               value="{{ old('subject', $announcement->subject ?? '') }}"
               class="form-input mt-1 @error('subject') form-input-error @enderror">
        @error('subject')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    {{-- Status --}}
    @php $currentStatus = old('status', $announcement->status ?? 'draft'); @endphp
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('vacancies.status') }} <span class="text-red-500">*</span></label>
        <div class="flex gap-3">

            {{-- Draft --}}
            <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border px-4 py-2.5 text-sm font-medium transition
                          {{ $currentStatus === 'draft'
                             ? 'border-yellow-400 bg-yellow-50 text-yellow-800 ring-2 ring-yellow-400'
                             : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50' }}">
                <input type="radio" name="status" value="draft"
                       class="sr-only" {{ $currentStatus === 'draft' ? 'checked' : '' }}>
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                {{ __('messages.draft') }}
            </label>

            {{-- Published --}}
            <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border px-4 py-2.5 text-sm font-medium transition
                          {{ $currentStatus === 'published'
                             ? 'border-green-400 bg-green-50 text-green-800 ring-2 ring-green-400'
                             : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50' }}">
                <input type="radio" name="status" value="published"
                       class="sr-only" {{ $currentStatus === 'published' ? 'checked' : '' }}>
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                {{ __('messages.published') }}
            </label>

        </div>
        @error('status')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    {{-- Published At --}}
    @php
        $pubDate = '';
        $pubTime = '';
        if (old('_pub_date')) {
            $pubDate = old('_pub_date');
            $pubTime = old('_pub_time', '');
        } elseif (isset($announcement) && $announcement->published_at) {
            $pubDate = $announcement->published_at->format('Y-m-d');
            $pubTime = $announcement->published_at->format('H:i');
        }
    @endphp

    @if(app()->getLocale() === 'am')
    <div class="space-y-3">
        <x-ethiopian-datepicker
            name="_pub_date"
            :label="__('messages.published_at')"
            :value="$pubDate"/>
        <div class="max-w-xs">
            <label class="block text-sm font-medium text-gray-700">{{ __('dashboard.table.time') }}</label>
            <input type="time" name="_pub_time" value="{{ $pubTime }}"
                   class="form-input mt-1">
        </div>
        <p class="text-xs text-gray-400">{{ __('messages.leave_blank_draft') }}</p>
    </div>
    @else
    <div class="max-w-xs">
        <label class="block text-sm font-medium text-gray-700">{{ __('messages.published_at') }}</label>
        <input type="datetime-local" name="published_at"
               value="{{ old('published_at', isset($announcement) && $announcement->published_at ? $announcement->published_at->format('Y-m-d\TH:i') : '') }}"
               class="form-input mt-1">
        <p class="mt-1 text-xs text-gray-400">{{ __('messages.leave_blank_draft') }}</p>
    </div>
    @endif

    {{-- Content (TinyMCE) --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.content') }} <span class="text-red-500">*</span></label>
        <textarea name="content" id="tinymce-content"
                  class="@error('content') form-input-error @enderror">{{ old('content', $announcement->content ?? '') }}</textarea>
        @error('content')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="flex gap-3 pt-2">
        <button type="submit" class="btn btn-primary">
            {{ $isEdit ? __('messages.save_changes') : __('messages.create') }}
        </button>
        <a href="{{ route('admin.announcements.index') }}" class="btn btn-secondary">{{ __('messages.cancel') }}</a>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
tinymce.init({
    selector: '#tinymce-content',
    height: 420,
    menubar: true,
    plugins: [
        'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
        'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
        'insertdatetime', 'media', 'table', 'wordcount'
    ],
    toolbar:
        'undo redo | blocks | bold italic underline | forecolor backcolor | ' +
        'alignleft aligncenter alignright alignjustify | ' +
        'bullist numlist outdent indent | link image table | code fullscreen',
    content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; font-size: 14px; }',
    branding: false,
    promotion: false,
    // Sync editor content back to the hidden textarea before the form submits,
    // otherwise the textarea stays empty and the required validation fails.
    setup: function (editor) {
        editor.on('change', function () { editor.save(); });
        editor.on('submit', function () { editor.save(); });
    },
});

// Catch the form's submit event and flush all TinyMCE instances first.
document.querySelector('form').addEventListener('submit', function () {
    if (typeof tinymce !== 'undefined') {
        tinymce.triggerSave();
    }
});
</script>
@endpush
