@props([
    'title',
    'subtitle' => null,
    'crumbs' => [],   // [['label' => ..., 'url' => ...|null], ...]
    'width' => 'max-w-7xl',
])

{{-- Light title band: breadcrumb, H1, optional subtitle, then any extra content (search, meta, actions). --}}
<section {{ $attributes->merge(['class' => 'border-b border-gray-200 bg-white']) }}>
    <div class="mx-auto {{ $width }} px-4 pb-8 pt-6 sm:px-6 lg:px-8">
        @if(!empty($crumbs))
        <nav aria-label="Breadcrumb" class="mb-3">
            <ol class="flex flex-wrap items-center gap-1.5 text-sm text-gray-500">
                <li><a href="{{ route('home') }}" class="font-medium text-brand hover:underline">{{ __('public.home') }}</a></li>
                @foreach($crumbs as $crumb)
                <li class="flex items-center gap-1.5">
                    <x-public.icon name="chevron-right" class="h-3.5 w-3.5 text-gray-400" />
                    @if(!empty($crumb['url']))
                        <a href="{{ $crumb['url'] }}" class="font-medium text-brand hover:underline">{{ $crumb['label'] }}</a>
                    @else
                        <span class="max-w-[18rem] truncate text-gray-700" aria-current="page">{{ $crumb['label'] }}</span>
                    @endif
                </li>
                @endforeach
            </ol>
        </nav>
        @endif

        <h1 class="max-w-4xl text-3xl font-extrabold leading-tight tracking-tight text-gray-900 sm:text-4xl">{{ $title }}</h1>

        @if($subtitle)
        <p class="mt-2 max-w-2xl text-base leading-relaxed text-gray-600">{{ $subtitle }}</p>
        @endif

        {{ $slot }}
    </div>
</section>
