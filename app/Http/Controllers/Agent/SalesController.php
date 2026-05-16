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
        $agentLevel = $request->get('agent_level', 'all');

        $descendants = collect();
        if (in_array($agent->agent_role, [Agent::ROLE_AGENT_LEADER, Agent::ROLE_BUSINESS_PARTNER], true)) {
            $descendants = $hierarchy->getAllDescendants($agent);
        }

        if ($agentLevel === 'own') {
            $earnerIds = collect([$agent->id]);
        } elseif ($agentLevel === 'leader') {
            $earnerIds = $descendants->where('agent_role', Agent::ROLE_AGENT_LEADER)->pluck('id');
        } elseif ($agentLevel === 'agent' || $agentLevel === 'agent_under') {
            $earnerIds = $descendants->where('agent_role', Agent::ROLE_AGENT)->pluck('id');
        } else {
            $earnerIds = collect([$agent->id])->merge($descendants->pluck('id'))->unique()->values();
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

        $query = Sale::with(['agent', 'commissions' => function ($q) use ($earnerIds) {
                $q->whereIn('earning_agent_id', $earnerIds)
                  ->where('is_reversal', false)
                  ->with('earningAgent');
            }]);

        $query->whereHas('commissions', function ($q) use ($earnerIds) {
            $q->whereIn('earning_agent_id', $earnerIds)
              ->where('is_reversal', false);
        });

        if ($startDate && $endDate) {
            $query->whereBetween('sale_date', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ]);
        }

        if ($status && $status !== 'all') {
            $query->whereHas('commissions', function ($q) use ($status, $earnerIds) {
                $q->where('status', $status)
                  ->whereIn('earning_agent_id', $earnerIds);
            });
        }

        $sales = $query->orderByDesc('sale_date')->paginate(10);
        $sales->through(function (Sale $s) use ($earnerIds) {
            return [
                'id' => $s->id,
                'invoice_number' => $s->invoice_number,
                'sale_date' => $s->sale_date?->toIso8601String(),
                'description' => $s->description,
                'sale_amount' => $s->amount,
                'source_agent' => $s->agent ? [
                    'id' => $s->agent->id,
                    'name' => $s->agent->name,
                    'agent_role' => $s->agent->agent_role,
                ] : null,
                'commissions' => $s->commissions->filter(function ($c) use ($earnerIds) {
                    return ! $c->is_reversal && $earnerIds->contains($c->earning_agent_id);
                })->map(function ($c) {
                    return [
                        'id' => $c->id,
                        'earning_agent' => $c->earningAgent ? [
                            'id' => $c->earningAgent->id,
                            'name' => $c->earningAgent->name,
                            'agent_role' => $c->earningAgent->agent_role,
                        ] : null,
                        'commission_type' => $c->commission_type,
                        'commission_amount' => $c->amount,
                        'commission_rate' => $c->commission_rate,
                        'commission_calc_type' => $c->commission_calc_type,
                        'commission_fixed_amount' => $c->commission_fixed_amount,
                        'status' => $c->status,
                    ];
                })->values(),
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
            'sales' => $sales,
            'totals' => [
                'sales' => (float) $totalSales,
                'commission' => (float) $totalCommission,
                'overrides' => (float) $totalOverrides,
            ],
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => $status,
                'agent_level' => $agentLevel,
            ],
            'agent' => $agent,
        ]);
    }
}
