<?php

declare(strict_types=1);

namespace App\Actions\Reports;

use App\Jobs\GenerateReportExportJob;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class ExportReportAction
{
    public function handle(
        string $reportType,
        User $requestedBy,
        array $filters = [],
        string $format = 'xlsx',
    ): string {
        if (! $requestedBy->hasPermissionTo('reports.export')) {
            throw new AuthorizationException('User does not have [reports.export] permission.');
        }

        $specificPermission = match ($reportType) {
            'applicants', 'vacancy-wise-applicants', 'final-selected', 'document-verification' => 'reports.applicants',
            'vacancies' => 'reports.vacancies',
            'screening', 'passed-screening', 'failed-screening' => 'reports.screening',
            'exam-interview', 'exam-shortlist', 'interview-shortlist' => 'reports.exam-interview',
            'audit', 'audit-log' => 'reports.audit',
            'notifications', 'notification' => 'notifications.view',
            default => 'reports.view',
        };

        if (! $requestedBy->hasPermissionTo($specificPermission)) {
            throw new AuthorizationException("User does not have [{$specificPermission}] permission.");
        }

        $format = in_array($format, ['xlsx', 'csv', 'pdf'], true) ? $format : 'xlsx';

        $fileName = sprintf(
            'reports/%s-%s-%s.%s',
            $reportType,
            $requestedBy->id,
            now()->format('Ymd-His'),
            $format,
        );

        GenerateReportExportJob::dispatch($reportType, $fileName, $filters, $requestedBy->id, $format);

        return $fileName;
    }
}
