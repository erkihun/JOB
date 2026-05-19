<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationTemplateController extends Controller
{
    public function index(): View
    {
        $templates = NotificationTemplate::orderBy('type')->orderBy('locale')->get();

        return view('admin.notification-templates.index', compact('templates'));
    }

    public function edit(NotificationTemplate $notificationTemplate): View
    {
        return view('admin.notification-templates.edit', ['template' => $notificationTemplate]);
    }

    public function update(Request $request, NotificationTemplate $notificationTemplate): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'active' => ['boolean'],
        ]);

        $notificationTemplate->update([
            'subject' => $data['subject'],
            'body' => $data['body'],
            'active' => $request->boolean('active'),
        ]);

        return redirect()->route('admin.notification-templates.index')
            ->with('success', __('messages.template_updated'));
    }
}
