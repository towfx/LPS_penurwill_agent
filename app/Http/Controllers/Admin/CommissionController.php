<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Commission;
use App\Models\Payout;
use App\Services\PayoutReportGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CommissionController extends Controller
{
    public function __construct(protected PayoutReportGenerator $reportGenerator) {}

    /**
     * Display the commissions list page (grouped by earning agent).
     */
    public function index(Request $request)
    {
        $year = (int) $request->get('year', date('Y'));
        $month = (int) $request->get('month', date('n'));

        $query = Commission::query()
            ->select([
                'earning_agent_id',
                DB::raw('SUM(amount) as total_commission'),
                DB::raw('COUNT(*) as total_sales'),
            ])
            ->whereNotNull('earning_agent_id')
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month);

        $this->applyCommissionFilters($query, $request);

        $commissions = $query
            ->groupBy('earning_agent_id')
            ->with(['earningAgent'])
            ->get();

        $payouts = Payout::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->get()
            ->keyBy('agent_id');

        $commissions->each(function ($commission) use ($payouts) {
            $commission->agent = $commission->earningAgent;
            $payout = $payouts->get($commission->earning_agent_id);
            $commission->payout = $payout ? [
                'id' => $payout->id,
                'status' => $payout->status,
                'paid_at' => $payout->paid_at,
                'amount' => $payout->amount,
            ] : null;
        });

        $currentYear = (int) date('Y');
        $years = range($currentYear - 9, $currentYear);
        $months = $this->monthLabels(true);

        return Inertia::render('Admin/CommissionsList', [
            'commissions' => $commissions,
            'years' => $years,
            'months' => $months,
            'selectedYear' => $year,
            'selectedMonth' => $month,
            'filters' => $this->currentFilters($request),
        ]);
    }

    /**
     * Display commission details for a specific agent, month, and year.
     */
    public function detail(Request $request)
    {
        $year = (int) $request->get('year', date('Y'));
        $month = (int) $request->get('month', date('n'));
        $agentId = $request->get('agent_id');

        if (! $agentId) {
            return redirect()->route('admin.commissions.list');
        }

        $agent = Agent::findOrFail($agentId);

        $summaryQuery = Commission::query()
            ->where('earning_agent_id', $agentId)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month);
        $this->applyCommissionFilters($summaryQuery, $request);

        $summary = (clone $summaryQuery)
            ->select([
                DB::raw('SUM(amount) as total_commission'),
                DB::raw('COUNT(*) as total_sales'),
            ])
            ->first();

        $commissions = (clone $summaryQuery)
            ->with(['sale'])
            ->orderBy('created_at', 'desc')
            ->get();

        $payout = Payout::where('agent_id', $agentId)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->first();

        $months = $this->monthLabels();

        return Inertia::render('Admin/CommissionDetail', [
            'agent' => $agent,
            'summary' => $summary,
            'commissions' => $commissions,
            'payout' => $payout,
            'year' => $year,
            'month' => $month,
            'monthName' => $months[$month] ?? 'Unknown',
            'filters' => $this->currentFilters($request),
            'breakdown' => [
                'by_commission_type' => $this->reportGenerator->byCommissionType($agent, $year, $month),
                'by_sales_source' => $this->reportGenerator->bySalesSource($agent, $year, $month),
                'transactions' => $this->reportGenerator->transactions($agent, $year, $month),
            ],
        ]);
    }

    /**
     * Apply optional filters: commission_type, commission_category, commission_calc_type.
     */
    protected function applyCommissionFilters($query, Request $request): void
    {
        if ($request->filled('commission_type')) {
            $query->where('commission_type', $request->commission_type);
        }
        if ($request->filled('commission_category')) {
            $query->where('commission_category', $request->commission_category);
        }
        if ($request->filled('commission_calc_type')) {
            $query->where('commission_calc_type', $request->commission_calc_type);
        }
    }

    protected function currentFilters(Request $request): array
    {
        return [
            'commission_type' => $request->get('commission_type'),
            'commission_category' => $request->get('commission_category'),
            'commission_calc_type' => $request->get('commission_calc_type'),
        ];
    }

    protected function monthLabels(bool $short = false): array
    {
        return $short ? [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec',
        ] : [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
        ];
    }
}
