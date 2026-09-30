<section class="space-y-3">
    <div class="flex items-center justify-between gap-3">
        <h2 class="card-title">{{ __('menus.vacancies') }}</h2>
        @can('vacancies.create')
            @if($announcement->opening_date && $announcement->closing_date)
            <a class="btn btn-primary" href="{{ route('admin.vacancies.create', ['announcement_id' => $announcement->id]) }}">{{ __('vacancies.create_vacancy') }}</a>
            @endif
        @endcan
    </div>
    @forelse($announcement->vacancies as $position)
        <div class="flex items-center justify-between gap-3 rounded-lg border border-gray-100 p-3 text-sm">
            <a class="text-brand" href="{{ route('admin.vacancies.show', $position) }}">{{ $position->code }} — {{ $position->title }}</a>
            <span class="text-gray-500">{{ $position->institution?->name }}</span>
        </div>
    @empty
        <p class="text-sm text-gray-500">{{ __('messages.no_records') }}</p>
    @endforelse
</section>
