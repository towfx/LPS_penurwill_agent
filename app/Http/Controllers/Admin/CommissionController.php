<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commission;
use App\Models\Agent;
use App\Models\Payout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CommissionController extends Controller
{
    /**
     * Display the commissions list page
     */
    public function index(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('n')); // 1-12

        // Get commissions grouped by agent for the selected month and year
        $commissions = Commission::select([
                'agent_id',
                DB::raw('SUM(amount) as total_commission'),
                DB::raw('COUNT(*) as total_sales')
            ])
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->with('agent')
            ->groupBy('agent_id')
            ->get();

        // Get payout information for each agent
        $payouts = Payout::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->get()
            ->keyBy('agent_id');

        // Combine commissions with payout data
        $commissions->each(function ($commission) use ($payouts) {
            $payout = $payouts->get($commission->agent_id);
            $commission->payout = $payout ? [
                'id' => $payout->id,
                'status' => $payout->status,
                'paid_at' => $payout->paid_at,
                'amount' => $payout->amount,
            ] : null;
        });

        // Get years for dropdown (last 10 years to current)
        $currentYear = date('Y');
        $years = range($currentYear - 9, $currentYear);

        // Get months for tabs
        $months = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'
        ];

        return Inertia::render('Admin/CommissionsList', [
            'commissions' => $commissions,
            'years' => $years,
            'months' => $months,
            'selectedYear' => (int) $year,
            'selectedMonth' => (int) $month,
        ]);
    }

    /**
     * Display commission details for a specific agent, month, and year
     */
    public function detail(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('n'));
        $agentId = $request->get('agent_id');

        if (!$agentId) {
            return redirect()->route('admin.commissions.list');
        }

        $agent = Agent::findOrFail($agentId);

        // Get commission summary
        $summary = Commission::select([
                DB::raw('SUM(amount) as total_commission'),
                DB::raw('COUNT(*) as total_sales')
            ])
            ->where('agent_id', $agentId)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->first();

        // Get detailed commissions
        $commissions = Commission::where('agent_id', $agentId)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->with(['sale'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Get payout for this agent, month, and year
        $payout = Payout::where('agent_id', $agentId)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->first();

        // Get months for display
        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];

        return Inertia::render('Admin/CommissionDetail', [
            'agent' => $agent,
            'summary' => $summary,
            'commissions' => $commissions,
            'payout' => $payout,
            'year' => (int) $year,
            'month' => (int) $month,
            'monthName' => $months[$month] ?? 'Unknown',
        ]);
    }
}
