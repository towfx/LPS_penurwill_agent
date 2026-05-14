<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Commission;
use App\Models\Sale;
use App\Services\AgentHierarchy;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SalesController extends Controller
{
    /**
     * Display the agent commissions list (one row per commission, joined with sale).
     *
     * Leaders / business partners see commissions earned by themselves and all
     * descendants in their downline. Plain agents see only their own.
     */
    public function index(Request $request, AgentHierarchy $hierarchy)
    {
        $agent = auth()->user()->agents()->first();

        if (! $agent) {
            return redirect()->route('agent.dashboard');
        }

        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $status = $request->get('status', 'pending');

        $earnerIds = collect([$agent->id]);
        if (in_array($agent->agent_role, [Agent::ROLE_AGENT_LEADER, Agent::ROLE_BUSINESS_PARTNER], true)) {
            $earnerIds = $earnerIds
                ->merge($hierarchy->getAllDescendants($agent)->pluck('id'))
                ->unique()
                ->values();
        }

        $applyDateFilter = function ($q) use ($startDate, $endDate) {
            if ($startDate && $endDate) {
                $q->whereHas('sale', function ($s) use ($startDate, $endDate) {
                    $s->whereBetween('sale_date', [
                        Carbon::parse($startDate)->startOfDay(),
                        Carbon::parse($endDate)->endOfDay(),
                    ]);
                });
            }
        };

        $applyStatusFilter = function ($q) use ($status) {
            if ($status && $status !== 'all') {
                $q->where('status', $status);
            }
        };

        // List query — exclude reversal rows from the table view.
        $listQuery = Commission::with(['sale.agent'])
            ->whereIn('earning_agent_id', $earnerIds)
            ->where('is_reversal', false);
        $applyDateFilter($listQuery);
        $applyStatusFilter($listQuery);

        $commissions = $listQuery
            ->orderByDesc(
                Sale::select('sale_date')->whereColumn('sales.id', 'commissions.sale_id')
            )
            ->get();

        $data = $commissions->map(function (Commission $c) {
            return [
                'id' => $c->id,
                'sale_id' => $c->sale_id,
                'sale_date' => $c->sale?->sale_date?->toIso8601String(),
                'invoice_number' => $c->sale?->invoice_number,
                'description' => $c->sale?->description,
                'sale_amount' => $c->sale?->amount,
                'commission_amount' => $c->amount,
                'commission_rate' => $c->commission_rate,
                'commission_calc_type' => $c->commission_calc_type,
                'commission_fixed_amount' => $c->commission_fixed_amount,
                'commission_type' => $c->commission_type,
                'commission_category' => $c->commission_category,
                'status' => $c->status,
                'source_agent' => $c->sale?->agent ? [
                    'id' => $c->sale->agent->id,
                    'name' => $c->sale->agent->name,
                    'agent_role' => $c->sale->agent->agent_role,
                ] : null,
            ];
        });

        // Card totals — include reversal rows so figures net out correctly.
        // Cards respect the status filter for consistency with the visible table.
        $cardBase = Commission::query()->whereIn('earning_agent_id', $earnerIds);
        $applyDateFilter($cardBase);
        $applyStatusFilter($cardBase);

        $totalCommission = (clone $cardBase)->ownSales()->sum('amount');
        $totalOverrides = (clone $cardBase)->overrides()->sum('amount');

        $saleIds = (clone $cardBase)->distinct()->pluck('sale_id')->filter();
        $totalSales = Sale::whereIn('id', $saleIds)->sum('amount');

        return Inertia::render('Agent/Sales', [
            'commissions' => $data,
            'totals' => [
                'sales' => (float) $totalSales,
                'commission' => (float) $totalCommission,
                'overrides' => (float) $totalOverrides,
            ],
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => $status,
            ],
            'agent' => $agent,
        ]);
    }
}
