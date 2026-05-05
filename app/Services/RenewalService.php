<?php

namespace App\Services;

use App\Mail\AgentExpiryAlertNotification;
use App\Mail\AgentRenewalReminderNotification;
use App\Models\Agent;
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
     */
    public function sendRenewalReminders(): int
    {
        $sent = 0;
        Agent::query()
            ->whereDate('renewal_due_at', now()->toDateString())
            ->where('fee_payment_status', '!=', Agent::FEE_STATUS_PAID)
            ->orWhere(function ($q) {
                $q->whereDate('renewal_due_at', now()->toDateString())
                  ->whereNotNull('expires_at');
            })
            ->cursor()
            ->each(function (Agent $agent) use (&$sent) {
                $email = $this->resolveEmail($agent);
                if (! $email) return;
                try {
                    Mail::to($email)->send(new AgentRenewalReminderNotification($agent));
                    $sent++;
                } catch (\Throwable $e) {
                    Log::warning('Renewal reminder failed', [
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
        return Agent::query()
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '<', now()->toDateString())
            ->where('fee_payment_status', '!=', Agent::FEE_STATUS_PAID)
            ->where('status', '!=', 'expired')
            ->update(['status' => 'expired']);
    }

    /**
     * Send a final alert to agents whose expires_at = today.
     */
    public function sendExpiryAlerts(): int
    {
        $sent = 0;
        Agent::query()
            ->whereDate('expires_at', now()->toDateString())
            ->where('fee_payment_status', '!=', Agent::FEE_STATUS_PAID)
            ->cursor()
            ->each(function (Agent $agent) use (&$sent) {
                $email = $this->resolveEmail($agent);
                if (! $email) return;
                try {
                    Mail::to($email)->send(new AgentExpiryAlertNotification($agent));
                    $sent++;
                } catch (\Throwable $e) {
                    Log::warning('Expiry alert failed', [
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
