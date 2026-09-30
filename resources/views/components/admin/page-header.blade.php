{{--
    Standard admin page header: optional breadcrumb, title, description, actions.
    Usage:
      <x-admin.page-header :title="__('menus.vacancies')" :description="…"
                           :crumbs="[['label' => __('menus.recruitment')]]">
          <a class="btn btn-primary" href="…">New</a>
      </x-admin.page-header>
--}}
@props([
    'title',
    'description' => null,
    'crumbs' => [],   // [['label' => ..., 'url' => ...|null], ...]
])

<header {{ $attributes->merge(['class' => 'page-header']) }}>
    <div class="min-w-0">
        @if(!empty($crumbs))
        <nav aria-label="Breadcrumb">
            <ol class="page-breadcrumb">
                @foreach($crumbs as $crumb)
                <li class="flex items-center gap-1.5">
                    @if(!$loop->first)<span aria-hidden="true" class="text-gray-400">/</span>@endif
                    @if(!empty($crumb['url']))
                        <a href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a>
                    @else
                        <span>{{ $crumb['label'] }}</span>
                    @endif
                </li>
                @endforeach
            </ol>
        </nav>
        @endif
        <h1 class="page-title">{{ $title }}</h1>
        @if($description)
        <p class="page-description">{{ $description }}</p>
        @endif
    </div>
    @if($slot->isNotEmpty())
    <div class="page-actions">{{ $slot }}</div>
    @endif
</header>
