<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\AgentNotification;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $agent = auth()->user()->agents()->first();
        if (! $agent) {
            return redirect()->route('agent.dashboard');
        }

        $notifications = AgentNotification::forAgent($agent->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return Inertia::render('Agent/Inbox', [
            'notifications' => $notifications,
        ]);
    }

    public function markRead(Request $request, int $id)
    {
        $agent = auth()->user()->agents()->first();
        $notification = AgentNotification::forAgent($agent->id)->findOrFail($id);
        $notification->markRead();

        return back();
    }

    public function markAllRead(Request $request)
    {
        $agent = auth()->user()->agents()->first();
        AgentNotification::forAgent($agent->id)
            ->unread()
            ->update(['status' => AgentNotification::STATUS_READ, 'read_at' => now()]);

        return back();
    }
}
