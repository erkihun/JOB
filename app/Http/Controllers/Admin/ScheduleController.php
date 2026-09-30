<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\ExamInterviewType;
use App\Http\Controllers\Controller;
use App\Models\ExamInterviewSchedule;
use App\Models\Vacancy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $filtered = function () use ($request) {
            $query = ExamInterviewSchedule::with('vacancy');
            if ($vacancyId = $request->get('vacancy_id')) {
                $query->where('vacancy_id', $vacancyId);
            }
            if ($type = $request->get('type')) {
                $query->where('type', $type);
            }

            return $query;
        };

        $view = $request->get('view') === 'list' ? 'list' : 'calendar';

        // Calendar: one month, weeks starting on Monday.
        try {
            $month = Carbon::createFromFormat('!Y-m', (string) $request->get('month', now()->format('Y-m')))->startOfMonth();
        } catch (\Throwable) {
            $month = now()->startOfMonth();
        }
        $gridStart = $month->copy()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $month->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);
        $calendarSchedules = $filtered()
            ->whereBetween('date', [$gridStart->toDateString(), $gridEnd->toDateString()])
            ->orderBy('date')->orderBy('start_time')
            ->get()
            ->groupBy(fn (ExamInterviewSchedule $s) => $s->date->toDateString());

        $schedules = $filtered()->latest('date')->paginate(20)->withQueryString();
        $vacancies = Vacancy::orderBy('title->en')->get(['id', 'title', 'code']);
        $types = ExamInterviewType::cases();

        return view('admin.schedules.index', compact(
            'schedules', 'vacancies', 'types', 'view', 'month', 'gridStart', 'gridEnd', 'calendarSchedules',
        ));
    }

    public function create(): View
    {
        $vacancies = Vacancy::orderBy('title->en')->get(['id', 'title', 'code']);
        $types = ExamInterviewType::cases();
        $schedule = new ExamInterviewSchedule;

        return view('admin.schedules.create', compact('schedule', 'vacancies', 'types'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules(creating: true));

        // One schedule per chosen vacancy (same date, time and venue), all or nothing.
        $vacancyIds = array_values(array_unique($data['vacancy_ids'] ?? [$data['vacancy_id']]));
        $attributes = Arr::except($data, ['vacancy_ids', 'vacancy_id']);

        DB::transaction(function () use ($vacancyIds, $attributes): void {
            foreach ($vacancyIds as $vacancyId) {
                ExamInterviewSchedule::create($attributes + ['vacancy_id' => $vacancyId, 'created_by' => auth()->id()]);
            }
        });

        return redirect()->route('admin.schedules.index')
            ->with('success', count($vacancyIds) > 1
                ? __('messages.schedules_created', ['count' => count($vacancyIds)])
                : __('messages.schedule_created'));
    }

    public function edit(ExamInterviewSchedule $schedule): View
    {
        $vacancies = Vacancy::orderBy('title->en')->get(['id', 'title', 'code']);
        $types = ExamInterviewType::cases();

        return view('admin.schedules.edit', compact('schedule', 'vacancies', 'types'));
    }

    public function update(Request $request, ExamInterviewSchedule $schedule): RedirectResponse
    {
        $data = $request->validate($this->rules());
        $schedule->update($data);

        return redirect()->route('admin.schedules.index')
            ->with('success', __('messages.schedule_updated'));
    }

    public function destroy(ExamInterviewSchedule $schedule): RedirectResponse
    {
        $schedule->delete();

        return redirect()->route('admin.schedules.index')
            ->with('success', __('messages.schedule_deleted'));
    }

    private function rules(bool $creating = false): array
    {
        $time = 'regex:/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/';

        return [
            // Create: several vacancies (vacancy_ids[]); edit keeps a single vacancy_id.
            'vacancy_id' => $creating ? ['required_without:vacancy_ids', 'nullable', 'exists:vacancies,id'] : ['required', 'exists:vacancies,id'],
            'vacancy_ids' => $creating ? ['required_without:vacancy_id', 'array', 'min:1'] : ['prohibited'],
            'vacancy_ids.*' => ['distinct', 'exists:vacancies,id'],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(ExamInterviewType::class)],
            // New schedules can't be placed in the past; existing ones may be edited as-is.
            'date' => $creating ? ['required', 'date', 'after_or_equal:today'] : ['required', 'date'],
            'start_time' => ['required', 'string', $time],
            'end_time' => ['nullable', 'string', $time, 'after:start_time'],
            'venue' => ['required', 'string', 'max:255'],
            'instruction' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
