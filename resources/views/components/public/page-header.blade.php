@props([
    'title',
    'subtitle' => null,
    'crumbs' => [],   // [['label' => ..., 'url' => ...|null], ...]
    'width' => 'max-w-7xl',
])

<section class="relative overflow-hidden bg-navy text-white">
    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
        <div class="absolute -top-24 right-0 h-72 w-72 rounded-full bg-brand/40 blur-3xl"></div>
        <div class="absolute -bottom-32 left-10 h-64 w-64 rounded-full bg-accent/15 blur-3xl"></div>
        <div class="absolute inset-0 opacity-[0.07]"
             style="background-image:linear-gradient(to right,#fff 1px,transparent 1px),linear-gradient(to bottom,#fff 1px,transparent 1px);background-size:40px 40px;"></div>
    </div>

    <div class="relative mx-auto {{ $width }} px-4 py-10 sm:px-6 sm:py-12 lg:px-8">
        @if(!empty($crumbs))
        <nav aria-label="Breadcrumb" class="mb-4">
            <ol class="flex flex-wrap items-center gap-1.5 text-xs font-medium text-white/60">
                <li><a href="{{ route('home') }}" class="hover:text-white">{{ __('public.home') }}</a></li>
                @foreach($crumbs as $crumb)
                <li class="flex items-center gap-1.5">
                    <x-public.icon name="chevron-right" class="h-3 w-3 text-white/30" />
                    @if(!empty($crumb['url']))
                        <a href="{{ $crumb['url'] }}" class="hover:text-white">{{ $crumb['label'] }}</a>
                    @else
                        <span class="max-w-[16rem] truncate text-white/85" aria-current="page">{{ $crumb['label'] }}</span>
                    @endif
                </li>
                @endforeach
            </ol>
        </nav>
        @endif

        <h1 class="max-w-3xl text-2xl font-extrabold leading-tight tracking-tight sm:text-3xl lg:text-4xl">{{ $title }}</h1>

        @if($subtitle)
        <p class="mt-3 max-w-2xl text-sm leading-relaxed text-white/70 sm:text-base">{{ $subtitle }}</p>
        @endif

        {{ $slot }}
    </div>
</section>
