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

        $tab = $request->get('tab', 'unread');
        $allowed = ['unread', 'pending', 'archived'];
        if (! in_array($tab, $allowed)) {
            $tab = 'unread';
        }

        $query = AgentNotification::forAgent($agent->id)->orderByDesc('created_at');

        if ($tab === 'unread') {
            $query->unread();
        } elseif ($tab === 'pending') {
            $query->pending();
        } else {
            $query->archived();
        }

        $notifications = $query->paginate(20)->withQueryString();

        $unreadCount = AgentNotification::forAgent($agent->id)->unread()->count();

        return Inertia::render('Agent/Inbox', [
            'notifications' => $notifications,
            'activeTab' => $tab,
            'unreadCount' => $unreadCount,
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
