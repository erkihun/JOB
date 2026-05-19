<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\ExamInterviewType;
use App\Http\Controllers\Controller;
use App\Models\ExamInterviewSchedule;
use App\Models\Vacancy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $query = ExamInterviewSchedule::with('vacancy')->latest('date');

        if ($vacancyId = $request->get('vacancy_id')) {
            $query->where('vacancy_id', $vacancyId);
        }
        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        $schedules = $query->paginate(20)->withQueryString();
        $vacancies = Vacancy::orderBy('title->en')->get(['id', 'title', 'code']);
        $types = ExamInterviewType::cases();

        return view('admin.schedules.index', compact('schedules', 'vacancies', 'types'));
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
        $data = $request->validate($this->rules());

        ExamInterviewSchedule::create(array_merge($data, ['created_by' => auth()->id()]));

        return redirect()->route('admin.schedules.index')
            ->with('success', __('messages.schedule_created'));
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

    private function rules(): array
    {
        return [
            'vacancy_id' => ['required', 'exists:vacancies,id'],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string'],
            'date' => ['required', 'date'],
            'start_time' => ['required', 'string'],
            'end_time' => ['nullable', 'string'],
            'venue' => ['required', 'string', 'max:255'],
            'instruction' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
