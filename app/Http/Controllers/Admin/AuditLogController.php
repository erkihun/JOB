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

        $logs = $query->paginate(30)->withQueryString();
        $modules = AuditLog::distinct()->orderBy('module')->pluck('module');
        $actions = AuditLog::distinct()->orderBy('action')->pluck('action');

        return view('admin.audit-logs.index', compact('logs', 'modules', 'actions'));
    }
}
