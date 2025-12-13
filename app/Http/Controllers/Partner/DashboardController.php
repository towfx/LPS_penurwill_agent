<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Agent;
use App\Models\Commission;
use App\Models\Partner;
use App\Models\Payout;
use App\Models\Referral;
use App\Models\Sale;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        if (! $user->hasRole('partner')) {
            abort(403, 'Partner access required');
        }

        // Get partner
        $partner = $user->partners->first();
        if (! $partner) {
            abort(403, 'No partner associated with this user');
        }

        // Get partner's agent IDs
        $agentIds = $partner->agents->pluck('id')->toArray();

        if (empty($agentIds)) {
            // Return empty dashboard if no agents
            return Inertia::render('Partner/Dashboard', [
                'stats' => [
                    'revenueThisMonth' => 0,
                    'revenueChange' => null,
                    'activeAgents' => 0,
                    'agentsChange' => null,
                    'commissionsPaid' => 0,
                    'commissionsChange' => null,
                    'conversionRate' => null,
                    'conversionChange' => null,
                ],
                'monthlyRevenue' => [],
                'topAgents' => [],
                'commissionDistribution' => ['pending' => 0, 'completed' => 0, 'cancelled' => 0],
                'referralsByDay' => [],
                'salesByDay' => [],
                'recentActivity' => [],
                'quickActions' => [
                    'pendingPayouts' => 0,
                    'pendingPayoutsAmount' => 0,
                    'totalAgents' => 0,
                    'activeAgentsCount' => 0,
                ],
                'systemHealth' => [
                    'avgConversionRate' => 0,
                    'avgCommissionRate' => 0,
                    'totalReferrals' => 0,
                    'totalSales' => 0,
                ],
            ]);
        }

        // 1. Stats Cards
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();

        // Total Revenue This Month (filtered by partner's agents)
        $revenueThisMonth = Sale::whereIn('agent_id', $agentIds)
            ->whereBetween('sale_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');
        $revenueLastMonth = Sale::whereIn('agent_id', $agentIds)
            ->whereBetween('sale_date', [$startOfLastMonth, $endOfLastMonth])
            ->sum('amount');
        $revenueChange = $revenueLastMonth > 0 ? (($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100 : null;

        // Active Agents (filtered by partner)
        $activeAgents = Agent::whereIn('id', $agentIds)->where('status', 'active')->count();
        $newAgentsThisMonth = Agent::whereIn('id', $agentIds)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();
        $newAgentsLastMonth = Agent::whereIn('id', $agentIds)
            ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->count();
        $agentsChange = $newAgentsLastMonth > 0 ? (($newAgentsThisMonth - $newAgentsLastMonth) / $newAgentsLastMonth) * 100 : null;

        // Total Commissions Paid (filtered by partner's agents)
        $commissionsPaid = Commission::where('status', 'completed')
            ->whereHas('sale', function ($q) use ($agentIds, $startOfMonth, $endOfMonth) {
                $q->whereIn('agent_id', $agentIds)
                    ->whereBetween('sale_date', [$startOfMonth, $endOfMonth]);
            })
            ->sum('amount');
        $commissionsPaidLastMonth = Commission::where('status', 'completed')
            ->whereHas('sale', function ($q) use ($agentIds, $startOfLastMonth, $endOfLastMonth) {
                $q->whereIn('agent_id', $agentIds)
                    ->whereBetween('sale_date', [$startOfLastMonth, $endOfLastMonth]);
            })
            ->sum('amount');
        $commissionsChange = $commissionsPaidLastMonth > 0 ? (($commissionsPaid - $commissionsPaidLastMonth) / $commissionsPaidLastMonth) * 100 : null;

        // System Conversion Rate (filtered by partner's agents)
        $referralsThisMonth = Referral::whereIn('referrer_id', $agentIds)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();
        $salesThisMonth = Sale::whereIn('agent_id', $agentIds)
            ->whereBetween('sale_date', [$startOfMonth, $endOfMonth])->count();
        $conversionRate = $referralsThisMonth > 0 ? ($salesThisMonth / $referralsThisMonth) * 100 : null;
        $referralsLastMonth = Referral::whereIn('referrer_id', $agentIds)
            ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->count();
        $salesLastMonth = Sale::whereIn('agent_id', $agentIds)
            ->whereBetween('sale_date', [$startOfLastMonth, $endOfLastMonth])->count();
        $conversionRateLastMonth = $referralsLastMonth > 0 ? ($salesLastMonth / $referralsLastMonth) * 100 : null;
        $conversionChange = ($conversionRateLastMonth && $conversionRate) ? ($conversionRate - $conversionRateLastMonth) : null;

        // 2. Monthly Revenue Line Chart (Last 12 months) - filtered by partner's agents
        $monthlyRevenue = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);
            $startOfMonth = $month->copy()->startOfMonth();
            $endOfMonth = $month->copy()->endOfMonth();

            $revenue = Sale::whereIn('agent_id', $agentIds)
                ->whereBetween('sale_date', [$startOfMonth, $endOfMonth])
                ->sum('amount');

            $monthlyRevenue[$month->format('M Y')] = $revenue;
        }

        // 3. Top Performing Agents Bar Chart (filtered by partner)
        $topAgents = Agent::whereIn('id', $agentIds)
            ->withCount(['sales as total_sales'])
            ->withSum('sales', 'amount')
            ->where('status', 'active')
            ->orderByDesc('sales_sum_amount')
            ->take(10)
            ->get()
            ->map(function ($agent) {
                return [
                    'name' => $agent->individual_name ?: $agent->company_name ?: 'Unknown',
                    'revenue' => $agent->sales_sum_amount ?? 0,
                    'sales_count' => $agent->total_sales ?? 0,
                ];
            });

        // 4. Commission Distribution Pie Chart (filtered by partner's agents)
        $commissionDistribution = [
            'pending' => Commission::where('status', 'pending')
                ->whereHas('sale', function ($q) use ($agentIds) {
                    $q->whereIn('agent_id', $agentIds);
                })
                ->count(),
            'completed' => Commission::where('status', 'completed')
                ->whereHas('sale', function ($q) use ($agentIds) {
                    $q->whereIn('agent_id', $agentIds);
                })
                ->count(),
            'cancelled' => Commission::where('status', 'cancelled')
                ->whereHas('sale', function ($q) use ($agentIds) {
                    $q->whereIn('agent_id', $agentIds);
                })
                ->count(),
        ];

        // 5. Referral vs Sales Conversion (Last 30 days) - filtered by partner's agents
        $period = CarbonPeriod::create($now->copy()->subDays(29), $now);
        $referralsByDay = [];
        $salesByDay = [];
        foreach ($period as $date) {
            $day = $date->format('Y-m-d');
            $referralsByDay[$day] = Referral::whereIn('referrer_id', $agentIds)
                ->whereDate('created_at', $date)->count();
            $salesByDay[$day] = Sale::whereIn('agent_id', $agentIds)
                ->whereDate('sale_date', $date)->count();
        }

        // 6. Recent System Activity (filtered by partner's agents)
        $recentActivity = ActivityLog::with(['user'])
            ->where(function ($q) use ($agentIds, $partner) {
                $q->whereHasMorph('target', [Agent::class], function ($subQ) use ($agentIds) {
                    $subQ->whereIn('id', $agentIds);
                })
                ->orWhere(function ($subQ) use ($partner) {
                    $subQ->where('target_type', Partner::class)
                        ->where('target_id', $partner->id);
                });
            })
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
                    'timestamp' => $log->created_at,
                ];
            });

        // 7. Quick Actions & System Health
        $pendingPayouts = Payout::where('status', 'pending')
            ->whereHas('agent', function ($q) use ($agentIds) {
                $q->whereIn('id', $agentIds);
            })
            ->count();
        $pendingPayoutsAmount = Payout::where('status', 'pending')
            ->whereHas('agent', function ($q) use ($agentIds) {
                $q->whereIn('id', $agentIds);
            })
            ->sum('amount');
        $totalAgents = Agent::whereIn('id', $agentIds)->count();
        $activeAgentsCount = Agent::whereIn('id', $agentIds)->where('status', 'active')->count();

        // 8. System Health Metrics (filtered by partner's agents)
        $avgConversionRate = $referralsThisMonth > 0 ? ($salesThisMonth / $referralsThisMonth) * 100 : 0;
        $avgCommissionRate = \App\Models\SystemSetting::first()?->commission_default_rate ?? 0;
        $totalReferrals = Referral::whereIn('referrer_id', $agentIds)->count();
        $totalSales = Sale::whereIn('agent_id', $agentIds)->count();

        return Inertia::render('Partner/Dashboard', [
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
