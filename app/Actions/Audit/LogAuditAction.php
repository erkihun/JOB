<?php

declare(strict_types=1);

namespace App\Actions\Audit;

use App\Models\AuditLog;

class LogAuditAction
{
    public function handle(
        string $action,
        string $module,
        ?string $recordId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
    ): AuditLog {
        return AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'module' => $module,
            'record_id' => $recordId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }
}
