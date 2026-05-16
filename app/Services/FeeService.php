<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Agent;
use App\Models\AgentNotification;
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
        $payment = DB::transaction(function () use ($agent, $recordedBy, $method, $reference) {
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

            $payment = FeePayment::create([
                'agent_id' => $agent->id,
                'fee_type' => FeePayment::TYPE_ENTRY,
                'role' => $role,
                'amount' => $amount,
                'payment_method' => $method,
                'payment_reference' => $reference,
                'status' => FeePayment::STATUS_CONFIRMED,
                'paid_at' => now(),
                'recorded_by' => $recordedBy->id,
            ]);

            ActivityLog::logCustom(
                $recordedBy,
                'fee_payment_recorded',
                "Entry fee of {$amount} recorded for agent #{$agent->id} (role: {$role})",
                $payment,
            );

            return $payment;
        });

        // Notify agent (after transaction commits)
        try {
            app(NotificationService::class)->notify(
                $agent,
                AgentNotification::TYPE_FEE_PAYMENT,
                'Entry Fee Recorded',
                "Your entry fee of {$payment->amount} has been recorded.",
                FeePayment::class,
                $payment->id,
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('FeeService: entry fee notification failed', [
                'agent_id' => $agent->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $payment;
    }

    /**
     * Record a renewal-fee payment and roll the agent's expiry forward.
     */
    public function applyRenewalFee(Agent $agent, User $recordedBy, string $method = FeePayment::METHOD_MANUAL, ?string $reference = null): FeePayment
    {
        $payment = DB::transaction(function () use ($agent, $recordedBy, $method, $reference) {
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

            $payment = FeePayment::create([
                'agent_id' => $agent->id,
                'fee_type' => FeePayment::TYPE_RENEWAL,
                'role' => $role,
                'amount' => $amount,
                'payment_method' => $method,
                'payment_reference' => $reference,
                'status' => FeePayment::STATUS_CONFIRMED,
                'paid_at' => now(),
                'recorded_by' => $recordedBy->id,
            ]);

            ActivityLog::logCustom(
                $recordedBy,
                'fee_payment_recorded',
                "Renewal fee of {$amount} recorded for agent #{$agent->id} (role: {$role})",
                $payment,
            );

            return $payment;
        });

        // Notify agent (after transaction commits)
        try {
            app(NotificationService::class)->notify(
                $agent,
                AgentNotification::TYPE_FEE_PAYMENT,
                'Renewal Fee Recorded',
                "Your renewal fee of {$payment->amount} has been recorded. Membership now valid until {$agent->expires_at}.",
                FeePayment::class,
                $payment->id,
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('FeeService: renewal fee notification failed', [
                'agent_id' => $agent->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $payment;
    }

    /**
     * Confirm a pending payment.
     */
    public function confirmPayment(FeePayment $payment, User $recordedBy): void
    {
        if ($payment->status === FeePayment::STATUS_CONFIRMED) {
            return;
        }

        DB::transaction(function () use ($payment, $recordedBy) {
            $agent = $payment->agent;
            $role = $payment->role ?: ($agent->agent_role ?? Agent::ROLE_AGENT);
            $duration = $this->getMembershipDurationDays();
            $reminderDays = $this->getRenewalReminderDays();

            if ($payment->fee_type === FeePayment::TYPE_ENTRY) {
                $registeredAt = now()->toDateString();
                $expiresAt = now()->addDays($duration)->toDateString();
                $renewalDueAt = now()->addDays(max(1, $duration - $reminderDays))->toDateString();

                $agent->update([
                    'registered_at' => $registeredAt,
                    'expires_at' => $expiresAt,
                    'renewal_due_at' => $renewalDueAt,
                    'fee_payment_status' => Agent::FEE_STATUS_PAID,
                ]);
            } else {
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
            }

            $payment->update([
                'status' => FeePayment::STATUS_CONFIRMED,
                'paid_at' => now(),
                'recorded_by' => $recordedBy->id,
            ]);

            ActivityLog::logCustom(
                $recordedBy,
                'fee_payment_confirmed',
                "Fee payment #{$payment->id} confirmed for agent #{$agent->id}",
                $payment,
            );
        });

        // Notify agent
        try {
            app(NotificationService::class)->notify(
                $payment->agent,
                AgentNotification::TYPE_FEE_PAYMENT,
                'Fee Payment Confirmed',
                "Your payment of {$payment->amount} has been confirmed.",
                FeePayment::class,
                $payment->id,
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('FeeService: payment confirmation notification failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Void a payment.
     */
    public function voidPayment(FeePayment $payment, User $recordedBy): void
    {
        $payment->update([
            'status' => FeePayment::STATUS_VOID,
        ]);

        ActivityLog::logCustom(
            $recordedBy,
            'fee_payment_voided',
            "Fee payment #{$payment->id} voided for agent #{$payment->agent_id}",
            $payment,
        );

        // Notify agent
        try {
            if ($payment->agent) {
                app(NotificationService::class)->notify(
                    $payment->agent,
                    AgentNotification::TYPE_FEE_PAYMENT,
                    'Fee Payment Voided',
                    "Your fee payment #{$payment->id} of {$payment->amount} has been voided.",
                    FeePayment::class,
                    $payment->id,
                );
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('FeeService: void notification failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
        }
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

    protected function getMembershipDurationDays(): int
    {
        return (int) (SystemSetting::first()?->membership_duration_days ?? 365);
    }

    protected function getRenewalReminderDays(): int
    {
        return (int) (SystemSetting::first()?->renewal_reminder_days_before ?? 30);
    }

    /**
     * Create a Stripe Checkout Session for a one-time agent registration fee.
     * Returns the session URL, or null if Stripe is not configured.
     */
    public function createCheckoutSession(Agent $agent, string $successUrl, string $cancelUrl): ?string
    {
        $stripeSecret = config('cashier.secret');
        if (! $stripeSecret) {
            return null;
        }

        $role = $agent->agent_role ?? Agent::ROLE_AGENT;
        $amount = $this->getFeeAmountFor($role, FeePayment::TYPE_ENTRY);
        $amountCents = (int) round($amount * 100);

        if ($amountCents <= 0) {
            return null;
        }

        try {
            $session = \Laravel\Cashier\Cashier::stripe()->checkout->sessions->create([
                'mode' => 'payment',
                'line_items' => [[
                    'price_data' => [
                        'currency' => config('cashier.currency', 'myr'),
                        'unit_amount' => $amountCents,
                        'product_data' => [
                            'name' => 'Agent Registration Fee',
                            'description' => 'One-time entry fee — ' . ucwords(str_replace('_', ' ', $role)),
                        ],
                    ],
                    'quantity' => 1,
                ]],
                'customer_email' => $agent->users()->first()?->email,
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'metadata' => [
                    'agent_id' => $agent->id,
                    'fee_type' => FeePayment::TYPE_ENTRY,
                    'agent_role' => $role,
                ],
            ]);

            return $session->url;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('FeeService: Stripe checkout creation failed', [
                'agent_id' => $agent->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
