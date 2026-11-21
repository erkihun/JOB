<?php

declare(strict_types=1);

namespace App\Jobs;

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
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class GenerateReportExportJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(
        private readonly string $reportType,
        private readonly string $fileName,
        private readonly array $filters,
        private readonly string $requestedById,
        private readonly string $format = 'xlsx',
    ) {}

    public function handle(): void
    {
        $export = match ($this->reportType) {
            'applicants' => new ApplicantsReportExport($this->filters),
            'vacancy-wise-applicants' => new VacancyWiseApplicantReportExport($this->filters),
            'screening' => new ScreeningReportExport($this->filters),
            'passed-screening' => new PassedScreeningReportExport($this->filters),
            'failed-screening' => new FailedScreeningReportExport($this->filters),
            'exam-interview' => new ExamInterviewReportExport($this->filters),
            'exam-shortlist' => new ExamShortlistReportExport($this->filters),
            'interview-shortlist' => new InterviewShortlistReportExport($this->filters),
            'final-selected' => new FinalSelectedApplicantsReportExport($this->filters),
            'document-verification' => new DocumentVerificationReportExport($this->filters),
            'notification', 'notifications' => new NotificationReportExport($this->filters),
            'audit', 'audit-log' => new AuditLogReportExport($this->filters),
            default => new ApplicantsReportExport($this->filters),
        };

        if ($this->format === 'pdf') {
            Storage::put($this->fileName, Pdf::loadHTML($this->renderHtml($export))->output());

            return;
        }

        Excel::store($export, $this->fileName, 'local');
    }

    private function renderHtml(ApplicantsReportExport $export): string
    {
        $rows = $export->collection();
        $headings = $export->headings();

        $headerHtml = collect($headings)
            ->map(fn (string $heading): string => '<th>'.e($heading).'</th>')
            ->implode('');

        $rowHtml = $rows
            ->map(fn (array $row): string => '<tr>'.collect($row)
                ->map(fn (mixed $cell): string => '<td>'.e((string) $cell).'</td>')
                ->implode('').'</tr>')
            ->implode('');

        // Abyssinica SIL (shipped in public/fonts, OFL-licensed) covers Ethiopic and Latin, so
        // Amharic names print correctly without any system font installed on the server.
        // dompdf has no synthetic bold, so the same file is registered for bold headings.
        $font = e(public_path('fonts/abyssinica/AbyssinicaSIL-Regular.ttf'));
        $style = "@font-face { font-family: 'Abyssinica SIL'; font-weight: normal; src: url('{$font}') format('truetype'); }"
            ."@font-face { font-family: 'Abyssinica SIL'; font-weight: bold; src: url('{$font}') format('truetype'); }"
            ."body { font-family: 'Abyssinica SIL', 'DejaVu Sans', sans-serif; font-size: 9px; }";

        return "<html><head><meta charset=\"UTF-8\"><style>{$style}</style></head><body><table border=\"1\" cellpadding=\"4\" cellspacing=\"0\"><thead><tr>{$headerHtml}</tr></thead><tbody>{$rowHtml}</tbody></table></body></html>";
    }
}
