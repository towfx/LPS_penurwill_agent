<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\Sale;
use App\Models\Commission;
use App\Models\Referral;
use App\Models\Payout;
use App\Models\Agent;
use App\Models\ActivityLog;
use App\Models\SystemSetting;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasRole('admin')) {
            abort(403, 'Admin access required');
        }

        // 1. Stats Cards
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();

        // Total Revenue This Month
        $revenueThisMonth = Sale::whereBetween('sale_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');
        $revenueLastMonth = Sale::whereBetween('sale_date', [$startOfLastMonth, $endOfLastMonth])
            ->sum('amount');
        $revenueChange = $revenueLastMonth > 0 ? (($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100 : null;

        // Active Agents
        $activeAgents = Agent::where('status', 'active')->count();
        $newAgentsThisMonth = Agent::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();
        $newAgentsLastMonth = Agent::whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->count();
        $agentsChange = $newAgentsLastMonth > 0 ? (($newAgentsThisMonth - $newAgentsLastMonth) / $newAgentsLastMonth) * 100 : null;

        // Total Commissions Paid
        $commissionsPaid = Commission::where('status', 'completed')
            ->whereHas('sale', function($q) use ($startOfMonth, $endOfMonth) {
                $q->whereBetween('sale_date', [$startOfMonth, $endOfMonth]);
            })
            ->sum('amount');
        $commissionsPaidLastMonth = Commission::where('status', 'completed')
            ->whereHas('sale', function($q) use ($startOfLastMonth, $endOfLastMonth) {
                $q->whereBetween('sale_date', [$startOfLastMonth, $endOfLastMonth]);
            })
            ->sum('amount');
        $commissionsChange = $commissionsPaidLastMonth > 0 ? (($commissionsPaid - $commissionsPaidLastMonth) / $commissionsPaidLastMonth) * 100 : null;

        // System Conversion Rate
        $referralsThisMonth = Referral::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();
        $salesThisMonth = Sale::whereBetween('sale_date', [$startOfMonth, $endOfMonth])->count();
        $conversionRate = $referralsThisMonth > 0 ? ($salesThisMonth / $referralsThisMonth) * 100 : null;
        $referralsLastMonth = Referral::whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->count();
        $salesLastMonth = Sale::whereBetween('sale_date', [$startOfLastMonth, $endOfLastMonth])->count();
        $conversionRateLastMonth = $referralsLastMonth > 0 ? ($salesLastMonth / $referralsLastMonth) * 100 : null;
        $conversionChange = ($conversionRateLastMonth && $conversionRate) ? ($conversionRate - $conversionRateLastMonth) : null;

        // 2. Monthly Revenue Line Chart (Last 12 months)
        $monthlyRevenue = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);
            $startOfMonth = $month->copy()->startOfMonth();
            $endOfMonth = $month->copy()->endOfMonth();

            $revenue = Sale::whereBetween('sale_date', [$startOfMonth, $endOfMonth])
                ->sum('amount');

            $monthlyRevenue[$month->format('M Y')] = $revenue;
        }

        // 3. Top Performing Agents Bar Chart
        $topAgents = Agent::withCount(['sales as total_sales'])
            ->withSum('sales', 'amount')
            ->where('status', 'active')
            ->orderByDesc('sales_sum_amount')
            ->take(10)
            ->get()
            ->map(function ($agent) {
                return [
                    'name' => $agent->individual_name ?: $agent->company_name ?: 'Unknown',
                    'revenue' => $agent->sales_sum_amount ?? 0,
                    'sales_count' => $agent->total_sales ?? 0
                ];
            });

        // 4. Commission Distribution Pie Chart
        $commissionDistribution = [
            'pending' => Commission::where('status', 'pending')->count(),
            'completed' => Commission::where('status', 'completed')->count(),
            'cancelled' => Commission::where('status', 'cancelled')->count(),
        ];

        // 5. Referral vs Sales Conversion (Last 30 days)
        $period = CarbonPeriod::create($now->copy()->subDays(29), $now);
        $referralsByDay = [];
        $salesByDay = [];
        foreach ($period as $date) {
            $day = $date->format('Y-m-d');
            $referralsByDay[$day] = Referral::whereDate('created_at', $date)->count();
            $salesByDay[$day] = Sale::whereDate('sale_date', $date)->count();
        }

        // 6. Recent System Activity
        $recentActivity = ActivityLog::with(['user'])
            ->orderByDesc('created_at')
            ->take(10)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'description' => $log->description,
                    'user_name' => $log->user ? $log->user->name : 'System',
                    'target_type' => $log->target_type,
                    'created_at' => $log->created_at->diffForHumans(),
                    'timestamp' => $log->created_at
                ];
            });

        // 7. Quick Actions & System Health
        $pendingPayouts = Payout::where('status', 'pending')->count();
        $pendingPayoutsAmount = Payout::where('status', 'pending')->sum('amount');
        $totalAgents = Agent::count();
        $activeAgentsCount = Agent::where('status', 'active')->count();
        $systemSettings = SystemSetting::first();

        // 8. System Health Metrics
        $avgConversionRate = $referralsThisMonth > 0 ? ($salesThisMonth / $referralsThisMonth) * 100 : 0;
        $avgCommissionRate = $systemSettings ? $systemSettings->commission_default_rate : 0;
        $totalReferrals = Referral::count();
        $totalSales = Sale::count();

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'revenueThisMonth' => $revenueThisMonth,
                'revenueChange' => $revenueChange,
                'activeAgents' => $activeAgents,
                'agentsChange' => $agentsChange,
                'commissionsPaid' => $commissionsPaid,
                'commissionsChange' => $commissionsChange,
                'conversionRate' => $conversionRate,
                'conversionChange' => $conversionChange,
            ],
            'monthlyRevenue' => $monthlyRevenue,
            'topAgents' => $topAgents,
            'commissionDistribution' => $commissionDistribution,
            'referralsByDay' => $referralsByDay,
            'salesByDay' => $salesByDay,
            'recentActivity' => $recentActivity,
            'quickActions' => [
                'pendingPayouts' => $pendingPayouts,
                'pendingPayoutsAmount' => $pendingPayoutsAmount,
                'totalAgents' => $totalAgents,
                'activeAgentsCount' => $activeAgentsCount,
            ],
            'systemHealth' => [
                'avgConversionRate' => $avgConversionRate,
                'avgCommissionRate' => $avgCommissionRate,
                'totalReferrals' => $totalReferrals,
                'totalSales' => $totalSales,
            ],
        ]);
    }
}
