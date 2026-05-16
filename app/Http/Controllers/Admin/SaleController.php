<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\ReversalWindowExpiredException;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Agent;
use App\Models\Commission;
use App\Models\Sale;
use App\Services\RefundService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SaleController extends Controller
{
    /**
     * Admin listing of commissions across all agents, optionally filtered by source agent.
     */
    public function index(Request $request)
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $status = $request->get('status', 'all');
        $agentId = $request->get('agent_id');

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

        $applyAgentFilter = function ($q) use ($agentId) {
            if ($agentId) {
                $q->whereHas('sale', fn ($s) => $s->where('agent_id', $agentId));
            }
        };

        $query = Sale::with(['agent', 'commissions.earningAgent'])
            ->whereHas('commissions', function ($q) {
                $q->where('is_reversal', false);
            });

        if ($startDate && $endDate) {
            $query->whereBetween('sale_date', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ]);
        }

        if ($agentId) {
            $query->where('agent_id', $agentId);
        }

        if ($status && $status !== 'all') {
            $query->whereHas('commissions', function ($q) use ($status) {
                $q->where('status', $status);
            });
        }

        $sales = $query->orderByDesc('sale_date')->paginate(10);
        $sales->through(function (Sale $s) {
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
                'commissions' => $s->commissions->filter(fn($c) => !$c->is_reversal)->map(function ($c) {
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

        $cardBase = Commission::query();
        $applyDateFilter($cardBase);
        $applyStatusFilter($cardBase);
        $applyAgentFilter($cardBase);

        $totalCommission = (clone $cardBase)->ownSales()->sum('amount');
        $totalOverrides = (clone $cardBase)->overrides()->sum('amount');

        $saleIds = (clone $cardBase)->distinct()->pluck('sale_id')->filter();
        $totalSales = Sale::whereIn('id', $saleIds)->sum('amount');

        $settings = \App\Models\SystemSetting::first();
        $roleLabels = [
            'agent' => $settings->role_name_agent ?? 'Agent',
            'agent_leader' => $settings->role_name_leader ?? 'Leader',
            'business_partner' => $settings->role_name_business_partner ?? 'Business Partner',
        ];

        $agents = Agent::orderByRaw("CASE WHEN profile_type = 'company' THEN company_name ELSE individual_name END")
            ->get(['id', 'profile_type', 'individual_name', 'company_name', 'agent_role'])
            ->map(fn ($a) => [
                'value' => (string) $a->id,
                'label' => $a->name.' ('.($roleLabels[$a->agent_role] ?? $a->agent_role).')',
            ])
            ->prepend(['value' => '', 'label' => 'All Agents'])
            ->values();

        return Inertia::render('Admin/Sales', [
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
                'agent_id' => $agentId,
            ],
            'agents' => $agents,
        ]);
    }

    /**
     * Mark a sale as refunded — reverses every commission tied to it via RefundService.
     */
    public function markAsRefunded(Request $request, Sale $sale, RefundService $refundService)
    {
        $admin = Auth::user();

        try {
            $reversals = $refundService->reverseSale($sale, $admin);
        } catch (ReversalWindowExpiredException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to refund sale: '.$e->getMessage()]);
        }

        ActivityLog::logCustom(
            $admin,
            'sale_refunded',
            "Admin marked sale #{$sale->id} as refunded; {$reversals->count()} commission(s) reversed.",
            $sale,
        );

        return back()->with('success', "Sale refunded. {$reversals->count()} commission(s) reversed.");
    }
}
