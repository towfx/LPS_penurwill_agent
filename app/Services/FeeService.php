<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\FeePayment;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Records entry/renewal fee payments for agents and updates lifecycle
 * fields (registered_at, expires_at, renewal_due_at, fee_payment_status).
 */
class FeeService
{
    /**
     * Record an entry-fee payment for the agent and set their initial
     * registration / expiry dates from SystemSetting.
     */
    public function applyEntryFee(Agent $agent, User $recordedBy, string $method = FeePayment::METHOD_MANUAL, ?string $reference = null): FeePayment
    {
        return DB::transaction(function () use ($agent, $recordedBy, $method, $reference) {
            $role = $agent->agent_role ?? Agent::ROLE_AGENT;
            $amount = $this->getFeeAmountFor($role, FeePayment::TYPE_ENTRY);
            $duration = $this->getMembershipDurationDays();
            $reminderDays = $this->getRenewalReminderDays();

            $registeredAt = now()->toDateString();
            $expiresAt = now()->addDays($duration)->toDateString();
            $renewalDueAt = now()->addDays(max(1, $duration - $reminderDays))->toDateString();

            $agent->update([
                'registered_at' => $registeredAt,
                'expires_at' => $expiresAt,
                'renewal_due_at' => $renewalDueAt,
                'fee_payment_status' => Agent::FEE_STATUS_PAID,
            ]);

            return FeePayment::create([
                'agent_id' => $agent->id,
                'fee_type' => FeePayment::TYPE_ENTRY,
                'role' => $role,
                'amount' => $amount,
                'payment_method' => $method,
                'payment_reference' => $reference,
                'paid_at' => now(),
                'recorded_by' => $recordedBy->id,
            ]);
        });
    }

    /**
     * Record a renewal-fee payment and roll the agent's expiry forward.
     */
    public function applyRenewalFee(Agent $agent, User $recordedBy, string $method = FeePayment::METHOD_MANUAL, ?string $reference = null): FeePayment
    {
        return DB::transaction(function () use ($agent, $recordedBy, $method, $reference) {
            $role = $agent->agent_role ?? Agent::ROLE_AGENT;
            $amount = $this->getFeeAmountFor($role, FeePayment::TYPE_RENEWAL);
            $duration = $this->getMembershipDurationDays();
            $reminderDays = $this->getRenewalReminderDays();

            $base = $agent->expires_at && $agent->expires_at->isFuture()
                ? $agent->expires_at->copy()
                : now();

            $expiresAt = $base->copy()->addDays($duration)->toDateString();
            $renewalDueAt = $base->copy()->addDays(max(1, $duration - $reminderDays))->toDateString();

            $agent->update([
                'expires_at' => $expiresAt,
                'renewal_due_at' => $renewalDueAt,
                'fee_payment_status' => Agent::FEE_STATUS_PAID,
                'status' => 'active',
            ]);

            return FeePayment::create([
                'agent_id' => $agent->id,
                'fee_type' => FeePayment::TYPE_RENEWAL,
                'role' => $role,
                'amount' => $amount,
                'payment_method' => $method,
                'payment_reference' => $reference,
                'paid_at' => now(),
                'recorded_by' => $recordedBy->id,
            ]);
        });
    }

    public function getFeeAmountFor(string $agentRole, string $feeType): float
    {
        $settings = SystemSetting::first();
        if (! $settings) {
            return 0.0;
        }

        $roleKey = match ($agentRole) {
            Agent::ROLE_BUSINESS_PARTNER => 'business_partner',
            Agent::ROLE_AGENT_LEADER => 'leader',
            default => 'agent',
        };

        $column = $feeType === FeePayment::TYPE_ENTRY
            ? "entry_fee_{$roleKey}"
            : "renewal_fee_{$roleKey}";

        return (float) ($settings->{$column} ?? 0);
    }

    public function isRenewalEnabled(string $agentRole): bool
    {
        $settings = SystemSetting::first();
        if (! $settings) {
            return true;
        }
        return match ($agentRole) {
            Agent::ROLE_AGENT_LEADER => (bool) ($settings->renewal_fee_leader_enabled ?? true),
            Agent::ROLE_AGENT => (bool) ($settings->renewal_fee_agent_enabled ?? true),
            default => true,
        };
    }

    protected function getMembershipDurationDays(): int
    {
        return (int) (SystemSetting::first()?->membership_duration_days ?? 365);
    }

    protected function getRenewalReminderDays(): int
    {
        return (int) (SystemSetting::first()?->renewal_reminder_days_before ?? 30);
    }
}
