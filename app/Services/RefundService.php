<?php

namespace App\Services;

use App\Exceptions\ReversalWindowExpiredException;
use App\Models\ActivityLog;
use App\Models\AgentNotification;
use App\Models\Commission;
use App\Models\Payout;
use App\Models\PayoutItem;
use App\Models\Sale;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Single entry point for commission reversals (Decision 17 / QNA-18 / Decision 18).
 * Both the admin "Mark as Refunded" UI and any future payment-gateway webhook
 * must call `reverseSale()` so the audit trail is consistent.
 */
class RefundService
{
    /**
     * Throws if the sale is older than the reversal_time_limit window.
     */
    public function checkReversalEligibility(Sale $sale): void
    {
        $limit = (int) (SystemSetting::first()?->reversal_time_limit ?? 60);
        if ($sale->created_at && $sale->created_at->lt(now()->subDays($limit))) {
            throw new ReversalWindowExpiredException(
                "Sale #{$sale->id} is older than the {$limit}-day reversal window."
            );
        }
    }

    /**
     * Reverse a sale by creating negative-amount, status=cancelled commission rows
     * for every commission tied to the sale.
     *
     * @return Collection<int, Commission>
     */
    public function reverseSale(Sale $sale, User $admin): Collection
    {
        $this->checkReversalEligibility($sale);

        $reversals = DB::transaction(function () use ($sale, $admin) {
            $reversals = collect();
            $affectedPayoutIds = [];
            $commissions = Commission::where('sale_id', $sale->id)
                ->where('is_reversal', false)
                ->get();

            foreach ($commissions as $original) {
                $payload = [
                    'sale_id' => $sale->id,
                    'agent_id' => $original->agent_id,
                    'commission_source' => $original->commission_source,
                    'applied_rate' => $original->applied_rate,
                    'commission_rate' => $original->commission_rate,
                    'amount' => -1 * (float) $original->amount,
                    'status' => Commission::STATUS_CANCELLED,
                    'is_reversal' => true,
                    'original_commission_id' => $original->id,
                ];
                foreach ([
                    'earning_agent_id', 'commission_type', 'commission_category',
                    'commission_calc_type', 'commission_fixed_amount',
                    'source_sale_amount', 'beneficiary_role',
                ] as $col) {
                    if (\Schema::hasColumn('commissions', $col)) {
                        $payload[$col] = $original->{$col};
                    }
                }

                $reversal = Commission::create($payload);

                // Mirror any PayoutItems linked to the original commission as
                // negative PayoutItems against the reversal commission so payout
                // totals stay accurate.
                $originalItems = PayoutItem::where('commission_id', $original->id)
                    ->whereHas('payout', fn ($q) => $q->where('status', '!=', 'paid'))
                    ->get();
                foreach ($originalItems as $item) {
                    PayoutItem::create([
                        'payout_id' => $item->payout_id,
                        'commission_id' => $reversal->id,
                        'commission_type' => $item->commission_type,
                        'commission_category' => $item->commission_category,
                        'amount' => -1 * (float) $item->amount,
                    ]);
                    $affectedPayoutIds[$item->payout_id] = true;
                }

                // Cancel the original so it cannot be paid out.
                if (\Schema::hasColumn('commissions', 'status')) {
                    $original->update(['status' => Commission::STATUS_CANCELLED]);
                }

                ActivityLog::logCustom(
                    $admin,
                    'commission_reversed',
                    "Reversed commission #{$original->id} for sale #{$sale->id} (amount {$original->amount})",
                    $reversal,
                );

                $reversals->push($reversal);
            }

            if (\Schema::hasColumn('sales', 'status')) {
                $sale->update(['status' => Sale::STATUS_CANCELLED]);
            }

            foreach (array_keys($affectedPayoutIds) as $payoutId) {
                $payout = Payout::find($payoutId);
                if (! $payout) {
                    continue;
                }
                $before = ['amount' => (float) $payout->amount];
                $newAmount = (float) $payout->payoutItems()->sum('amount');
                $payout->update(['amount' => $newAmount]);

                ActivityLog::logCustom(
                    $admin,
                    'payout_recalculated',
                    "Payout #{$payout->id} amount recalculated from {$before['amount']} to {$newAmount} after sale #{$sale->id} reversal",
                    $payout,
                );
            }

            return $reversals;
        });

        // Notify all earners in the commission chain (after transaction commits)
        if ($reversals->isNotEmpty()) {
            try {
                $notificationService = app(NotificationService::class);
                $notificationService->notifyChain(
                    $reversals->first(),
                    AgentNotification::TYPE_COMMISSION_REVERSED,
                    'Commission Reversed',
                    "A commission for sale #{$sale->id} has been reversed.",
                );
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('RefundService: notifyChain failed', [
                    'sale_id' => $sale->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $reversals;
    }
}
