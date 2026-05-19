<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\AuditLog;
use Illuminate\Support\Collection;

class AuditLogReportExport extends ApplicantsReportExport
{
    public function __construct(private readonly array $filters = []) {}

    public function headings(): array
    {
        return [
            'Date/Time',
            'User',
            'Action',
            'Module',
            'Record ID',
            'IP Address',
        ];
    }

    protected function rows(): Collection
    {
        $query = AuditLog::query()
            ->with(['user'])
            ->when(isset($this->filters['module']), fn ($q) => $q->where('module', $this->filters['module']))
            ->when(isset($this->filters['action']), fn ($q) => $q->where('action', $this->filters['action']))
            ->when(isset($this->filters['user_id']), fn ($q) => $q->where('user_id', $this->filters['user_id']));

        $rows = [];
        foreach ($query->lazy() as $log) {
            $rows[] = [
                $log->created_at?->format('Y-m-d H:i:s') ?? '',
                $log->user?->name ?? 'System',
                $log->action,
                $log->module,
                $log->record_id ?? '',
                $log->ip_address ?? '',
            ];
        }

        return collect($rows);
    }
}
