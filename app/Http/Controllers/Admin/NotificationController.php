<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgentNotification;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationController extends Controller
{
    private const ADMIN_AGENT_ID = 1;

    public function index(Request $request)
    {
        $tab = $request->get('tab', 'unread');
        $allowed = ['unread', 'pending', 'archived'];
        if (! in_array($tab, $allowed)) {
            $tab = 'unread';
        }

        $query = AgentNotification::forAgent(self::ADMIN_AGENT_ID)->orderByDesc('created_at');

        if ($tab === 'unread') {
            $query->unread();
        } elseif ($tab === 'pending') {
            $query->pending();
        } else {
            $query->archived();
        }

        $notifications = $query->paginate(20)->withQueryString();

        $unreadCount = AgentNotification::forAgent(self::ADMIN_AGENT_ID)->unread()->count();

        return Inertia::render('Admin/Inbox', [
            'notifications' => $notifications,
            'activeTab' => $tab,
            'unreadCount' => $unreadCount,
        ]);
    }

    public function markRead(Request $request, int $id)
    {
        $notification = AgentNotification::forAgent(self::ADMIN_AGENT_ID)->findOrFail($id);
        $notification->markRead();

        return back();
    }

    public function markAllRead(Request $request)
    {
        AgentNotification::forAgent(self::ADMIN_AGENT_ID)
            ->unread()
            ->update(['status' => AgentNotification::STATUS_READ, 'read_at' => now()]);

        return back();
    }
}
