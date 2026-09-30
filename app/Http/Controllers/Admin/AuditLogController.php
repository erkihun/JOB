<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = AuditLog::with('user')->latest();

        if ($module = $request->get('module')) {
            $query->where('module', $module);
        }
        if ($action = $request->get('action')) {
            $query->where('action', $action);
        }
        if ($search = $request->get('search')) {
            $query->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $request->get('date_from')) === 1) {
            $query->whereDate('created_at', '>=', $request->get('date_from'));
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $request->get('date_until')) === 1) {
            $query->whereDate('created_at', '<=', $request->get('date_until'));
        }

        $logs = $query->paginate(30)->withQueryString();
        $modules = AuditLog::distinct()->orderBy('module')->pluck('module');
        $actions = AuditLog::distinct()->orderBy('action')->pluck('action');

        return view('admin.audit-logs.index', compact('logs', 'modules', 'actions'));
    }
}
