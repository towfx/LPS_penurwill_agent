<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Commission;
use App\Models\Payout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CommissionController extends Controller
{
    /**
     * Display the agent commissions list page
     */
    public function index(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $agent = auth()->user()->agents()->first();

        if (!$agent) {
            return redirect()->route('agent.dashboard');
        }

        // Get commissions grouped by month for the selected year
        $commissions = Commission::select([
                DB::raw('MONTH(created_at) as month'),
                DB::raw('SUM(amount) as total_commission'),
                DB::raw('COUNT(*) as total_sales')
            ])
            ->where('agent_id', $agent->id)
            ->whereYear('created_at', $year)
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->orderBy('month')
            ->get();

        // Get payout information for each month
        $payouts = Payout::where('agent_id', $agent->id)
            ->whereYear('created_at', $year)
            ->get()
            ->keyBy(function ($payout) {
                return $payout->created_at->format('n'); // Month number
            });

        // Get years for dropdown (last 10 years to current)
        $currentYear = date('Y');
        $years = range($currentYear - 9, $currentYear);

        // Get months for display
        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];

        // Combine commissions with payout data
        $commissionData = $commissions->map(function ($commission) use ($payouts, $months) {
            $monthName = $months[$commission->month] ?? 'Unknown';
            $payout = $payouts->get($commission->month);

            return [
                'month' => $commission->month,
                'month_name' => $monthName,
                'total_sales' => $commission->total_sales,
                'total_commission' => $commission->total_commission,
                'payout' => $payout ? [
                    'id' => $payout->id,
                    'status' => $payout->status,
                    'paid_at' => $payout->paid_at,
                    'amount' => $payout->amount,
                ] : null,
            ];
        });

        return Inertia::render('Agent/Commissions', [
            'commissions' => $commissionData,
            'years' => $years,
            'selectedYear' => (int) $year,
            'agent' => $agent,
        ]);
    }

    /**
     * Display commission details for a specific month and year
     */
    public function detail(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('n'));
        $agent = auth()->user()->agents()->first();

        if (!$agent) {
            return redirect()->route('agent.dashboard');
        }

        // Get commission summary
        $summary = Commission::select([
                DB::raw('SUM(amount) as total_commission'),
                DB::raw('COUNT(*) as total_sales')
            ])
            ->where('agent_id', $agent->id)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->first();

        // Get detailed commissions
        $commissions = Commission::where('agent_id', $agent->id)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->with(['sale'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Get payout for this month
        $payout = Payout::where('agent_id', $agent->id)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->first();

        // Get months for display
        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];

        return Inertia::render('Agent/CommissionDetail', [
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
