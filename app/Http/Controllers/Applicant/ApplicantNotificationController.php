<?php

declare(strict_types=1);

namespace App\Http\Controllers\Applicant;

use App\Http\Controllers\Controller;
use App\Models\ApplicantNotification;
use Illuminate\View\View;

class ApplicantNotificationController extends Controller
{
    public function index(): View
    {
        $applicant = auth()->user()->applicant;

        $notifications = $applicant
            ? ApplicantNotification::where('applicant_id', $applicant->id)
                ->orderByDesc('created_at')
                ->paginate(20)
            : collect();

        // Mark all as read when viewing the list
        if ($applicant) {
            ApplicantNotification::where('applicant_id', $applicant->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }

        return view('applicant.notifications.index', compact('notifications'));
    }
}
