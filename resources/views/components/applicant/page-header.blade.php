{{-- Portal page header: optional breadcrumb, title, description, actions (slot). --}}
@props([
    'title',
    'description' => null,
    'crumbs' => [],
])

<header {{ $attributes->merge(['class' => 'flex flex-wrap items-end justify-between gap-4']) }}>
    <div class="min-w-0">
        @if(!empty($crumbs))
        <nav aria-label="Breadcrumb" class="mb-1.5">
            <ol class="flex flex-wrap items-center gap-1.5 text-sm text-gray-600">
                @foreach($crumbs as $crumb)
                <li class="flex items-center gap-1.5">
                    @if(!$loop->first)<span class="text-gray-400" aria-hidden="true">/</span>@endif
                    @if(!empty($crumb['url']))
                        <a href="{{ $crumb['url'] }}" class="font-medium text-brand hover:underline">{{ $crumb['label'] }}</a>
                    @else
                        <span class="text-gray-800">{{ $crumb['label'] }}</span>
                    @endif
                </li>
                @endforeach
            </ol>
        </nav>
        @endif
        <h1 class="text-2xl font-extrabold tracking-tight text-gray-900 sm:text-3xl">{{ $title }}</h1>
        @if($description)
        <p class="mt-1.5 text-[15px] text-gray-600">{{ $description }}</p>
        @endif
    </div>
    @if($slot->isNotEmpty())
    <div class="flex flex-wrap items-center gap-2">{{ $slot }}</div>
    @endif
</header>
