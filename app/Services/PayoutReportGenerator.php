<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\PayoutItem;
use Illuminate\Support\Facades\DB;

/**
 * Builds the four tabbed report views for an agent's payout breakdown
 * (Decision 10). All queries hit `payout_items` joined with `payouts`
 * using the denormalized `commission_type` / `commission_category` columns
 * (Decision 3).
 */
class PayoutReportGenerator
{
    /**
     * Tab 1: totals grouped by commission_type (own_sales vs override).
     */
    public function byCommissionType(Agent $agent, int $year, int $month): array
    {
        $rows = PayoutItem::query()
            ->join('payouts', 'payout_items.payout_id', '=', 'payouts.id')
            ->where('payouts.agent_id', $agent->id)
            ->whereYear('payouts.created_at', $year)
            ->whereMonth('payouts.created_at', $month)
            ->groupBy('payout_items.commission_type')
            ->select(
                'payout_items.commission_type',
                DB::raw('SUM(payout_items.amount) as total'),
                DB::raw('COUNT(*) as count'),
            )
            ->get();

        return $rows->map(fn ($r) => [
            'commission_type' => $r->commission_type,
            'total' => (float) $r->total,
            'count' => (int) $r->count,
        ])->all();
    }

    /**
     * Tab 2: totals grouped by source agent (which subordinate's sale generated this).
     */
    public function bySalesSource(Agent $agent, int $year, int $month): array
    {
        $rows = PayoutItem::query()
            ->join('payouts', 'payout_items.payout_id', '=', 'payouts.id')
            ->join('commissions', 'payout_items.commission_id', '=', 'commissions.id')
            ->join('sales', 'commissions.sale_id', '=', 'sales.id')
            ->leftJoin('agents', 'sales.agent_id', '=', 'agents.id')
            ->where('payouts.agent_id', $agent->id)
            ->whereYear('payouts.created_at', $year)
            ->whereMonth('payouts.created_at', $month)
            ->groupBy('sales.agent_id')
            ->select(
                'sales.agent_id as source_agent_id',
                DB::raw('SUM(payout_items.amount) as total'),
                DB::raw('COUNT(*) as count'),
            )
            ->get();

        return $rows->map(fn ($r) => [
            'source_agent_id' => $r->source_agent_id,
            'total' => (float) $r->total,
            'count' => (int) $r->count,
        ])->all();
    }

    /**
     * Tab 3: totals grouped by month between two dates.
     */
    public function byTimePeriod(Agent $agent, $from, $to): array
    {
        $rows = PayoutItem::query()
            ->join('payouts', 'payout_items.payout_id', '=', 'payouts.id')
            ->where('payouts.agent_id', $agent->id)
            ->whereBetween('payouts.created_at', [$from, $to])
            ->groupBy(DB::raw("strftime('%Y-%m', payouts.created_at)"))
            ->select(
                DB::raw("strftime('%Y-%m', payouts.created_at) as period"),
                DB::raw('SUM(payout_items.amount) as total'),
            )
            ->get();

        return $rows->map(fn ($r) => [
            'period' => $r->period,
            'total' => (float) $r->total,
        ])->all();
    }

    /**
     * Tab 4: flat list of payout transactions for the period.
     */
    public function transactions(Agent $agent, int $year, int $month): array
    {
        return PayoutItem::query()
            ->join('payouts', 'payout_items.payout_id', '=', 'payouts.id')
            ->where('payouts.agent_id', $agent->id)
            ->whereYear('payouts.created_at', $year)
            ->whereMonth('payouts.created_at', $month)
            ->orderByDesc('payouts.created_at')
            ->select(
                'payout_items.id',
                'payout_items.commission_id',
                'payout_items.commission_type',
                'payout_items.commission_category',
                'payout_items.amount',
                'payouts.id as payout_id',
                'payouts.created_at as payout_created_at',
            )
            ->get()
            ->toArray();
    }
}
