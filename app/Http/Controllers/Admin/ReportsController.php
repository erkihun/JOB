<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Audit\LogAuditAction;
use App\Enums\ApplicationStatus;
use App\Exports\ApplicantsReportExport;
use App\Exports\AuditLogReportExport;
use App\Exports\DocumentVerificationReportExport;
use App\Exports\ExamInterviewReportExport;
use App\Exports\ExamShortlistReportExport;
use App\Exports\FailedScreeningReportExport;
use App\Exports\FinalSelectedApplicantsReportExport;
use App\Exports\InterviewShortlistReportExport;
use App\Exports\NotificationReportExport;
use App\Exports\PassedScreeningReportExport;
use App\Exports\ScreeningReportExport;
use App\Exports\VacancyWiseApplicantReportExport;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Vacancy;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportsController extends Controller
{
    /**
     * Downloadable reports: key => [export class, permission, translation key prefix, icon group].
     * Only these keys can be requested, and each one is gated by its own permission.
     */
    public const REPORTS = [
        'applicants' => [ApplicantsReportExport::class, 'reports.applicants', 'messages.report_applicants', 'applicants'],
        'vacancy-wise' => [VacancyWiseApplicantReportExport::class, 'reports.vacancies', 'admin.reports_center.cards.vacancy_wise_applicants', 'applicants'],
        'final-selected' => [FinalSelectedApplicantsReportExport::class, 'reports.applicants', 'admin.reports_center.cards.final_selected', 'applicants'],
        'screening' => [ScreeningReportExport::class, 'reports.screening', 'messages.report_screening', 'screening'],
        'passed-screening' => [PassedScreeningReportExport::class, 'reports.screening', 'admin.reports_center.cards.passed_screening', 'screening'],
        'failed-screening' => [FailedScreeningReportExport::class, 'reports.screening', 'admin.reports_center.cards.failed_screening', 'screening'],
        'document-verification' => [DocumentVerificationReportExport::class, 'reports.screening', 'admin.reports_center.cards.document_verification', 'screening'],
        'exam-shortlist' => [ExamShortlistReportExport::class, 'reports.exam-interview', 'admin.reports_center.cards.exam_shortlist', 'assessment'],
        'interview-shortlist' => [InterviewShortlistReportExport::class, 'reports.exam-interview', 'admin.reports_center.cards.interview_shortlist', 'assessment'],
        'exam-interview-results' => [ExamInterviewReportExport::class, 'reports.exam-interview', 'messages.report_exam_results', 'assessment'],
        'notifications' => [NotificationReportExport::class, 'reports.applicants', 'admin.reports_center.cards.notification_report', 'system'],
        'audit-log' => [AuditLogReportExport::class, 'reports.audit', 'admin.reports_center.cards.audit_log', 'system'],
    ];

    /** Statuses that mean the applicant got through screening (now or at a later stage). */
    private const PASSED_OR_LATER = [
        ApplicationStatus::PassedScreening, ApplicationStatus::ShortlistedExam, ApplicationStatus::ExamCompleted,
        ApplicationStatus::ShortlistedInterview, ApplicationStatus::InterviewCompleted, ApplicationStatus::Selected,
        ApplicationStatus::Waitlisted, ApplicationStatus::NotSelected,
    ];

    private const IN_REVIEW = [ApplicationStatus::Submitted, ApplicationStatus::UnderReview, ApplicationStatus::CorrectionRequired];

    public function index(Request $request): View
    {
        $filters = $this->filters($request);
        $base = $this->filteredQuery($filters);

        $applications = (clone $base)->with(['applicant', 'vacancy.announcement'])
            ->latest('applications.created_at')->paginate(25)->withQueryString();

        $statusCounts = (clone $base)->selectRaw('applications.status as status, count(*) as total')
            ->groupBy('applications.status')->pluck('total', 'status')->map(fn ($n) => (int) $n);
        $total = $statusCounts->sum();
        $count = fn (array $statuses): int => collect($statuses)->sum(fn (ApplicationStatus $s) => $statusCounts[$s->value] ?? 0);
        $pct = fn (int $n): float => $total > 0 ? round($n / $total * 100, 1) : 0.0;

        $screened = $count(self::PASSED_OR_LATER) + $count([ApplicationStatus::FailedScreening]);
        $kpis = [
            'total' => ['value' => $total, 'rate' => null],
            'in_review' => ['value' => $count(self::IN_REVIEW), 'rate' => $pct($count(self::IN_REVIEW))],
            'passed' => ['value' => $count(self::PASSED_OR_LATER), 'rate' => $screened > 0 ? round($count(self::PASSED_OR_LATER) / $screened * 100, 1) : 0.0],
            'failed' => ['value' => $count([ApplicationStatus::FailedScreening]), 'rate' => $screened > 0 ? round($count([ApplicationStatus::FailedScreening]) / $screened * 100, 1) : 0.0],
            'selected' => ['value' => $count([ApplicationStatus::Selected]), 'rate' => $pct($count([ApplicationStatus::Selected]))],
        ];

        $pipeline = collect(ApplicationStatus::cases())
            ->map(fn (ApplicationStatus $s) => ['status' => $s, 'count' => $statusCounts[$s->value] ?? 0, 'pct' => $pct($statusCounts[$s->value] ?? 0)])
            ->filter(fn ($row) => $row['count'] > 0)
            ->values();

        $byVacancy = $this->byVacancy($base);
        $gender = $this->genderSplit($base, $total);
        $trend = $this->trend($base, $filters);

        $vacancies = Vacancy::orderBy('title->en')->get(['id', 'title', 'code']);
        $statuses = ApplicationStatus::cases();
        $reports = $this->availableReports($request);

        // Kept for backward compatibility with anything reading the old summary.
        $summary = ['total' => $total, 'passed_screening' => $statusCounts[ApplicationStatus::PassedScreening->value] ?? 0, 'failed_screening' => $kpis['failed']['value']];

        return view('admin.reports.index', compact(
            'applications', 'vacancies', 'statuses', 'summary', 'kpis', 'pipeline', 'byVacancy', 'gender', 'trend', 'reports', 'filters', 'total',
        ));
    }

    public function export(Request $request, string $report, LogAuditAction $audit): BinaryFileResponse
    {
        abort_unless(isset(self::REPORTS[$report]), 404);
        [$class, $permission] = self::REPORTS[$report];
        abort_unless($request->user()?->hasPermissionTo($permission), 403);

        $filters = $this->filters($request);

        $audit->handle(
            action: 'report_exported',
            module: 'reports',
            newValues: ['report' => $report, 'filters' => $filters],
        );

        return Excel::download(new $class($filters), str_replace('-', '_', $report).'_'.now()->format('Ymd_His').'.xlsx');
    }

    /** @return array<string, string> */
    private function filters(Request $request): array
    {
        $filters = array_filter([
            'vacancy_id' => (string) $request->query('vacancy_id', ''),
            'status' => in_array($request->query('status'), array_column(ApplicationStatus::cases(), 'value'), true) ? (string) $request->query('status') : '',
            'date_from' => $this->validDate($request->query('date_from')),
            'date_until' => $this->validDate($request->query('date_until')),
        ], fn ($v) => $v !== '' && $v !== null);

        return $filters;
    }

    private function validDate(mixed $value): string
    {
        return is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1 ? $value : '';
    }

    /** @return Builder<Application> */
    private function filteredQuery(array $filters): Builder
    {
        return Application::query()
            ->when($filters['vacancy_id'] ?? null, fn ($q, $v) => $q->where('applications.vacancy_id', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('applications.status', $v))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('applications.created_at', '>=', $v))
            ->when($filters['date_until'] ?? null, fn ($q, $v) => $q->whereDate('applications.created_at', '<=', $v));
    }

    private function byVacancy(Builder $base): Collection
    {
        $passed = implode(',', array_map(fn ($s) => "'".$s->value."'", self::PASSED_OR_LATER));
        $rows = (clone $base)
            ->selectRaw("applications.vacancy_id, count(*) as total,
                sum(case when applications.status in ({$passed}) then 1 else 0 end) as passed,
                sum(case when applications.status = ? then 1 else 0 end) as failed", [ApplicationStatus::FailedScreening->value])
            ->groupBy('applications.vacancy_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $vacancies = Vacancy::whereIn('id', $rows->pluck('vacancy_id'))->get(['id', 'title', 'code'])->keyBy('id');

        return $rows->map(function ($row) use ($vacancies) {
            $screened = (int) $row->passed + (int) $row->failed;

            return [
                'vacancy' => $vacancies[$row->vacancy_id] ?? null,
                'total' => (int) $row->total,
                'passed' => (int) $row->passed,
                'failed' => (int) $row->failed,
                'pass_rate' => $screened > 0 ? round($row->passed / $screened * 100) : null,
            ];
        });
    }

    private function genderSplit(Builder $base, int $total): Collection
    {
        return (clone $base)
            ->join('applicants', 'applicants.id', '=', 'applications.applicant_id')
            ->selectRaw("coalesce(applicants.gender, 'unknown') as gender, count(*) as total")
            ->groupBy('gender')
            ->pluck('total', 'gender')
            ->map(fn ($n, $g) => ['gender' => (string) $g, 'count' => (int) $n, 'pct' => $total > 0 ? round($n / $total * 100) : 0])
            ->sortByDesc('count')
            ->values();
    }

    /** Applications received per day (or per week for long ranges) over the filtered window. */
    private function trend(Builder $base, array $filters): array
    {
        $until = CarbonImmutable::parse($filters['date_until'] ?? today())->startOfDay();
        $from = isset($filters['date_from']) ? CarbonImmutable::parse($filters['date_from'])->startOfDay() : $until->subDays(29);
        if ($from->greaterThan($until)) {
            [$from, $until] = [$until, $from];
        }

        $daily = (clone $base)
            ->whereDate('applications.created_at', '>=', $from->toDateString())
            ->whereDate('applications.created_at', '<=', $until->toDateString())
            ->selectRaw('date(applications.created_at) as day, count(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $days = (int) $from->diffInDays($until) + 1;
        $bucket = $days > 92 ? 7 : 1;   // long ranges: weekly bars so the chart stays readable
        $points = [];
        for ($d = $from; $d->lessThanOrEqualTo($until); $d = $d->addDays($bucket)) {
            $sum = 0;
            for ($i = 0; $i < $bucket; $i++) {
                $sum += (int) ($daily[$d->addDays($i)->toDateString()] ?? 0);
            }
            $points[] = ['date' => $d, 'count' => $sum];
        }

        return [
            'points' => $points,
            'max' => max(1, ...array_column($points, 'count')),
            'sum' => array_sum(array_column($points, 'count')),
            'weekly' => $bucket === 7,
            'from' => $from,
            'until' => $until,
        ];
    }

    private function availableReports(Request $request): Collection
    {
        $user = $request->user();
        $canExport = $user?->hasPermissionTo('reports.export') ?? false;

        return collect(self::REPORTS)
            ->filter(fn ($def) => $canExport && $user->hasPermissionTo($def[1]))
            ->map(fn ($def, $key) => [
                'key' => $key,
                'title' => __($def[2].'.title'),
                'description' => __($def[2].'.description'),
                'group' => $def[3],
            ]);
    }
}
