<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;

class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('audit.view');
    }

    public function view(User $user, AuditLog $auditLog): bool
    {
        return $user->hasPermissionTo('audit.view');
    }

    public function delete(User $user, AuditLog $auditLog): bool
    {
        return $user->isSuperAdmin() && $user->hasPermissionTo('audit.delete');
    }

    public function export(User $user): bool
    {
        return $user->hasPermissionTo('audit.export') || $user->hasPermissionTo('reports.audit');
    }
}
