<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $tab = $request->get('tab', 'unread');
        $allowed = ['unread', 'pending', 'archived'];
        if (! in_array($tab, $allowed)) {
            $tab = 'unread';
        }

        $query = AdminNotification::forUser($user->id)->orderByDesc('created_at');

        if ($tab === 'unread') {
            $query->unread();
        } elseif ($tab === 'pending') {
            $query->pending();
        } else {
            $query->archived();
        }

        $notifications = $query->paginate(20)->withQueryString();

        $unreadCount = AdminNotification::forUser($user->id)->unread()->count();

        return Inertia::render('Admin/Inbox', [
            'notifications' => $notifications,
            'activeTab' => $tab,
            'unreadCount' => $unreadCount,
        ]);
    }

    public function markRead(Request $request, int $id)
    {
        $user = auth()->user();
        $notification = AdminNotification::forUser($user->id)->findOrFail($id);
        $notification->markRead();

        return back();
    }

    public function markAllRead(Request $request)
    {
        $user = auth()->user();
        AdminNotification::forUser($user->id)
            ->unread()
            ->update(['status' => AdminNotification::STATUS_READ, 'read_at' => now()]);

        return back();
    }
}
