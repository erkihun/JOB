<section class="card overflow-hidden" aria-labelledby="ann-vacancies">
    <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-5 py-4 sm:px-6">
        <div>
            <h2 id="ann-vacancies" class="card-title">{{ __('menus.vacancies') }}</h2>
            <p class="mt-0.5 text-sm text-gray-600">{{ trans_choice('messages.ann_vacancy_count', $announcement->vacancies->count(), ['count' => $announcement->vacancies->count()]) }}</p>
        </div>
        @can('vacancies.create')
            @if($announcement->opening_date && $announcement->closing_date)
            <a class="btn btn-primary btn-sm" href="{{ route('admin.vacancies.create', ['announcement_id' => $announcement->id]) }}">+ {{ __('vacancies.create_vacancy') }}</a>
            @endif
        @endcan
    </div>
    @if($announcement->vacancies->isEmpty())
        <x-admin.empty :title="__('messages.ann_no_vacancies')" :text="__('messages.ann_no_vacancies_hint')" />
    @else
    <ul class="divide-y divide-gray-100">
        @foreach($announcement->vacancies as $position)
        <li class="flex flex-col gap-1 px-5 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <a class="min-w-0 font-semibold text-gray-900 hover:text-brand" href="{{ route('admin.vacancies.show', $position) }}">
                <span class="font-mono text-xs text-gray-500">{{ $position->code }}</span>
                <span class="ml-1">{{ $position->title }}</span>
            </a>
            <span class="flex items-center gap-3 text-sm text-gray-600">
                @if($position->institution)<span class="truncate">{{ $position->institution->name }}</span>@endif
                <x-admin.status :status="$position->status" />
            </span>
        </li>
        @endforeach
    </ul>
    @endif
</section>
