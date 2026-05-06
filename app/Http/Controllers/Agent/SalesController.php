<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Sale;
use App\Services\AgentHierarchy;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SalesController extends Controller
{
    /**
     * Display the agent sales list page (includes subordinate sales for leaders/BPs).
     */
    public function index(Request $request, AgentHierarchy $hierarchy)
    {
        $agent = auth()->user()->agents()->first();

        if (! $agent) {
            return redirect()->route('agent.dashboard');
        }

        // Get filter parameters
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $status = $request->get('status', 'pending'); // Default to pending

        // Determine agent ID scope: include subordinates for leaders + BPs
        $agentIds = collect([$agent->id]);
        if (in_array($agent->agent_role, [Agent::ROLE_AGENT_LEADER, Agent::ROLE_BUSINESS_PARTNER], true)) {
            $agentIds = $agentIds->merge(
                $hierarchy->getAllDescendants($agent)->pluck('id')
            )->unique()->values();
        }

        // Build query
        $query = Sale::with(['commission', 'agent'])
            ->whereIn('agent_id', $agentIds);

        // Apply date range filter
        if ($startDate && $endDate) {
            $query->whereBetween('sale_date', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ]);
        }

        // Apply commission status filter
        if ($status && $status !== 'all') {
            $query->whereHas('commission', function ($q) use ($status) {
                $q->where('status', $status);
            });
        }

        // Order by sale_date descending
        $sales = $query->orderBy('sale_date', 'desc')->get();

        // Format sales data for frontend
        $salesData = $sales->map(function ($sale) {
            return [
                'id' => $sale->id,
                'sale_date' => $sale->sale_date?->toIso8601String(),
                'description' => $sale->description,
                'invoice_number' => $sale->invoice_number,
                'amount' => $sale->amount,
                'commission' => $sale->commission ? [
                    'amount' => $sale->commission->amount,
                    'status' => $sale->commission->status,
                ] : null,
                'sale_agent' => $sale->agent ? [
                    'id' => $sale->agent->id,
                    'name' => $sale->agent->name,
                ] : null,
            ];
        });

        return Inertia::render('Agent/Sales', [
            'sales' => $salesData,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => $status,
            ],
            'agent' => $agent,
        ]);
    }
}
