<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Mail\SuspensionAppealNotification;
use App\Models\Agent;
use App\Models\AgentNotification;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AppealController extends Controller
{
    public function store(Request $request, NotificationService $notificationService)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $agent = auth()->user()->agents()->first();
        if (! $agent) {
            return back()->withErrors(['error' => 'Agent not found.']);
        }

        // Notify agent (confirmation)
        $notificationService->notify(
            $agent,
            AgentNotification::TYPE_APPROVAL_REQUESTED,
            'Appeal Submitted',
            'Your suspension appeal has been submitted and is under review.',
        );

        // Inbox row for admin (rich SuspensionAppealNotification email below — avoid duplicate generic email)
        $notificationService->notifyAdminInboxOnly(
            AgentNotification::TYPE_APPEAL_RECEIVED,
            "Suspension Appeal — {$agent->name}",
            "Agent #{$agent->id} ({$agent->name}) has appealed their suspension: {$request->message}",
            Agent::class,
            $agent->id,
        );

        // Email admin
        try {
            $adminEmail = config('mail.admin_address', config('mail.from.address'));
            if ($adminEmail) {
                Mail::to($adminEmail)->send(new SuspensionAppealNotification($agent, $request->message));
            }
        } catch (\Throwable $e) {
            Log::warning('AppealController: email failed', ['agent_id' => $agent->id, 'error' => $e->getMessage()]);
        }

        return back()->with('success', 'Your appeal has been submitted.');
    }
}
