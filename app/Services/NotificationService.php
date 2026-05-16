<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentNotification;
use App\Models\Commission;
use Illuminate\Support\Facades\Log;

/**
 * Creates in-app AgentNotification rows and dispatches email jobs.
 * Notification failure must never block the primary action — all methods are wrapped in try/catch.
 */
class NotificationService
{
    /**
     * Create a notification for an agent and dispatch a generic InboxNotificationEmail.
     * Email dispatch is skipped if the agent has opted out via email_notifications_enabled.
     */
    public function notify(
        Agent $agent,
        string $type,
        string $subject,
        string $body,
        ?string $relatedModel = null,
        ?int $relatedId = null
    ): ?AgentNotification {
        return $this->createNotification($agent, $type, $subject, $body, $relatedModel, $relatedId, true);
    }

    /**
     * Create an in-app notification WITHOUT dispatching the generic InboxNotificationEmail.
     * Use this when a richer Mailable is already being sent for the same event.
     */
    public function notifyInboxOnly(
        Agent $agent,
        string $type,
        string $subject,
        string $body,
        ?string $relatedModel = null,
        ?int $relatedId = null
    ): ?AgentNotification {
        return $this->createNotification($agent, $type, $subject, $body, $relatedModel, $relatedId, false);
    }

    /**
     * Send a notification to Admin (Agent#1 by convention).
     */
    public function notifyAdmin(
        string $type,
        string $subject,
        string $body,
        ?string $relatedModel = null,
        ?int $relatedId = null
    ): ?AgentNotification {
        $admin = Agent::find(1);
        if (! $admin) {
            return null;
        }

        return $this->notify($admin, $type, $subject, $body, $relatedModel, $relatedId);
    }

    /**
     * Send an admin notification without the generic InboxNotificationEmail.
     */
    public function notifyAdminInboxOnly(
        string $type,
        string $subject,
        string $body,
        ?string $relatedModel = null,
        ?int $relatedId = null
    ): ?AgentNotification {
        $admin = Agent::find(1);
        if (! $admin) {
            return null;
        }

        return $this->notifyInboxOnly($admin, $type, $subject, $body, $relatedModel, $relatedId);
    }

    /**
     * Notify all unique earners in a commission chain.
     */
    public function notifyChain(Commission $commission, string $type, string $subject, string $body): void
    {
        try {
            $saleCommissions = Commission::where('sale_id', $commission->sale_id)
                ->with('earningAgent')
                ->get();

            $notified = [];
            foreach ($saleCommissions as $c) {
                if (! $c->earningAgent || in_array($c->earningAgent->id, $notified)) {
                    continue;
                }
                $notified[] = $c->earningAgent->id;
                $this->notify(
                    $c->earningAgent,
                    $type,
                    $subject,
                    $body,
                    Commission::class,
                    $c->id,
                );
            }
        } catch (\Throwable $e) {
            Log::warning('NotificationService::notifyChain failed', [
                'commission_id' => $commission->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function createNotification(
        Agent $agent,
        string $type,
        string $subject,
        string $body,
        ?string $relatedModel,
        ?int $relatedId,
        bool $sendEmail
    ): ?AgentNotification {
        try {
            $notification = AgentNotification::create([
                'agent_id' => $agent->id,
                'type' => $type,
                'subject' => $subject,
                'body' => $body,
                'status' => AgentNotification::STATUS_UNREAD,
                'related_model' => $relatedModel,
                'related_id' => $relatedId,
            ]);

            if ($sendEmail) {
                $this->dispatchEmail($notification, $agent);
            }

            return $notification;
        } catch (\Throwable $e) {
            Log::warning('NotificationService::createNotification failed', [
                'agent_id' => $agent->id,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function dispatchEmail(AgentNotification $notification, Agent $agent): void
    {
        if ($agent->email_notifications_enabled === false) {
            Log::debug('NotificationService email skipped — agent opted out', [
                'notification_id' => $notification->id,
                'agent_id' => $agent->id,
            ]);
            return;
        }

        try {
            $user = $agent->users()->first();
            if (! $user) {
                return;
            }

            \Illuminate\Support\Facades\Mail::to($user->email)
                ->send(new \App\Mail\InboxNotificationEmail($notification));
        } catch (\Throwable $e) {
            Log::warning('NotificationService email dispatch failed', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
