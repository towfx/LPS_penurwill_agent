<?php

namespace App\Services;

use App\Mail\AgentExpiryAlertNotification;
use App\Mail\AgentRenewalReminderNotification;
use App\Models\ActivityLog;
use App\Models\Agent;
use App\Models\AgentNotification;
use App\Support\SystemUser;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Lifecycle management — fires reminder emails, marks expired agents,
 * and sends expiry alerts. Driven by the daily scheduler.
 */
class RenewalService
{
    /**
     * Send renewal reminders to agents whose renewal_due_at is today.
     * In-app notification is primary; email is secondary and skipped if agent opted out.
     */
    public function sendRenewalReminders(): int
    {
        $notificationService = app(NotificationService::class);
        $sent = 0;

        Agent::query()
            ->whereDate('renewal_due_at', now()->toDateString())
            ->where('fee_payment_status', '!=', Agent::FEE_STATUS_PAID)
            ->orWhere(function ($q) {
                $q->whereDate('renewal_due_at', now()->toDateString())
                  ->whereNotNull('expires_at');
            })
            ->cursor()
            ->each(function (Agent $agent) use ($notificationService, &$sent) {
                $notificationService->notifyInboxOnly(
                    $agent,
                    AgentNotification::TYPE_RENEWAL_REMINDER,
                    'Renewal Reminder',
                    "Your membership renewal is due. Please renew to keep your account active.",
                    Agent::class,
                    $agent->id,
                );

                if ($agent->email_notifications_enabled === false) {
                    return;
                }

                $email = $this->resolveEmail($agent);
                if (! $email) {
                    return;
                }

                try {
                    Mail::to($email)->send(new AgentRenewalReminderNotification($agent));
                    $sent++;
                } catch (\Throwable $e) {
                    Log::warning('Renewal reminder email failed', [
                        'agent_id' => $agent->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            });

        return $sent;
    }

    /**
     * Mark agents as expired whose expires_at < today and who have not paid.
     */
    public function markExpiredAgents(): int
    {
        $agents = Agent::query()
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '<', now()->toDateString())
            ->where('fee_payment_status', '!=', Agent::FEE_STATUS_PAID)
            ->where('status', '!=', 'expired')
            ->get();

        if ($agents->isEmpty()) {
            return 0;
        }

        Agent::whereIn('id', $agents->pluck('id'))->update(['status' => 'expired']);

        $systemUser = SystemUser::resolve();
        if ($systemUser) {
            foreach ($agents as $agent) {
                ActivityLog::logCustom(
                    $systemUser,
                    'agent_expired_by_scheduler',
                    "Scheduler marked agent #{$agent->id} as expired (expires_at: {$agent->expires_at})",
                    $agent,
                );
            }
        }

        $notificationService = app(NotificationService::class);
        foreach ($agents as $agent) {
            try {
                $notificationService->notify(
                    $agent,
                    AgentNotification::TYPE_AGENT_EXPIRED,
                    'Membership Expired',
                    "Your membership expired on {$agent->expires_at}. Renew to restore your account.",
                    Agent::class,
                    $agent->id,
                );
            } catch (\Throwable $e) {
                Log::warning('RenewalService: expiry notification failed', [
                    'agent_id' => $agent->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $agents->count();
    }

    /**
     * Send a final alert to agents whose expires_at = today.
     * In-app notification is primary; email is secondary and skipped if agent opted out.
     */
    public function sendExpiryAlerts(): int
    {
        $notificationService = app(NotificationService::class);
        $sent = 0;

        Agent::query()
            ->whereDate('expires_at', now()->toDateString())
            ->where('fee_payment_status', '!=', Agent::FEE_STATUS_PAID)
            ->cursor()
            ->each(function (Agent $agent) use ($notificationService, &$sent) {
                $notificationService->notifyInboxOnly(
                    $agent,
                    AgentNotification::TYPE_EXPIRY_ALERT,
                    'Membership Expiring Today',
                    "Your membership expires today. Renew now to avoid losing access.",
                    Agent::class,
                    $agent->id,
                );

                if ($agent->email_notifications_enabled === false) {
                    return;
                }

                $email = $this->resolveEmail($agent);
                if (! $email) {
                    return;
                }

                try {
                    Mail::to($email)->send(new AgentExpiryAlertNotification($agent));
                    $sent++;
                } catch (\Throwable $e) {
                    Log::warning('Expiry alert email failed', [
                        'agent_id' => $agent->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            });

        return $sent;
    }

    protected function resolveEmail(Agent $agent): ?string
    {
        return $agent->company_email_address
            ?? $agent->individual_email
            ?? optional($agent->users()->first())->email;
    }
}
