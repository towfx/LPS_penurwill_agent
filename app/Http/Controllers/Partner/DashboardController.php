<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Commission;
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
        // If user has multiple agents, use the first (most users will have one)
        $agent = $user->agents()->first();
        if (! $agent) {
            abort(403, 'Not an agent');
        }
        $agentId = $agent->id;

        // 1. Stats Cards
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();
        $start90 = $now->copy()->subDays(89)->startOfDay();
        $end90 = $now->copy()->endOfDay();
        $startPrev90 = $now->copy()->subDays(179)->startOfDay();
        $endPrev90 = $now->copy()->subDays(90)->endOfDay();

        // Total sales this month
        $salesThisMonth = Sale::where('agent_id', $agentId)
            ->whereBetween('sale_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');
        $salesLastMonth = Sale::where('agent_id', $agentId)
            ->whereBetween('sale_date', [$startOfLastMonth, $endOfLastMonth])
            ->sum('amount');
        $salesChange = $salesLastMonth > 0 ? (($salesThisMonth - $salesLastMonth) / $salesLastMonth) * 100 : null;

        // Total commissions earned this month (own_sales + overrides)
        $commThisMonth = Commission::where('earning_agent_id', $agentId)
            ->whereHas('sale', function ($q) use ($startOfMonth, $endOfMonth) {
                $q->whereBetween('sale_date', [$startOfMonth, $endOfMonth]);
            })
            ->sum('amount');
        $commLastMonth = Commission::where('earning_agent_id', $agentId)
            ->whereHas('sale', function ($q) use ($startOfLastMonth, $endOfLastMonth) {
                $q->whereBetween('sale_date', [$startOfLastMonth, $endOfLastMonth]);
            })
            ->sum('amount');

        // Commission breakdown by type (own_sales vs override) for this month
        $commByType = Commission::query()
            ->where('earning_agent_id', $agentId)
            ->whereHas('sale', function ($q) use ($startOfMonth, $endOfMonth) {
                $q->whereBetween('sale_date', [$startOfMonth, $endOfMonth]);
            })
            ->selectRaw('commission_type, SUM(amount) as total')
            ->groupBy('commission_type')
            ->pluck('total', 'commission_type');
        $ownSalesTotal = (float) ($commByType[Commission::TYPE_OWN_SALES] ?? 0);
        $overrideTotal = (float) ($commByType[Commission::TYPE_OVERRIDE] ?? 0);

        // Subordinate count (relevant for leaders / business partners)
        $subordinateCount = $agent->subordinates()->count();
        $commChange = $commLastMonth > 0 ? (($commThisMonth - $commLastMonth) / $commLastMonth) * 100 : null;

        // Active referrals (90 days)
        $referrals90 = Referral::where('referrer_id', $agentId)
            ->whereBetween('created_at', [$start90, $end90])
            ->count();
        $referralsPrev90 = Referral::where('referrer_id', $agentId)
            ->whereBetween('created_at', [$startPrev90, $endPrev90])
            ->count();
        $refChange = $referralsPrev90 > 0 ? (($referrals90 - $referralsPrev90) / $referralsPrev90) * 100 : null;

        // Conversion rate (sales/referrals, this month)
        $referralsThisMonth = Referral::where('referrer_id', $agentId)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->count();
        $conversionsThisMonth = Sale::where('agent_id', $agentId)
            ->whereBetween('sale_date', [$startOfMonth, $endOfMonth])
            ->count();
        $conversionRate = $referralsThisMonth > 0 ? ($conversionsThisMonth / $referralsThisMonth) * 100 : null;
        $referralsLastMonth = Referral::where('referrer_id', $agentId)
            ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
            ->count();
        $conversionsLastMonth = Sale::where('agent_id', $agentId)
            ->whereBetween('sale_date', [$startOfLastMonth, $endOfLastMonth])
            ->count();
        $conversionRateLastMonth = $referralsLastMonth > 0 ? ($conversionsLastMonth / $referralsLastMonth) * 100 : null;
        $conversionChange = ($conversionRateLastMonth && $conversionRate) ? ($conversionRate - $conversionRateLastMonth) : null;

        // 2. Sales line chart (last 30 days, by day)
        $start30 = $now->copy()->subDays(29)->startOfDay();
        $end30 = $now->copy()->endOfDay();
        $salesByDay = [];
        $period30 = CarbonPeriod::create($start30, $end30);
        foreach ($period30 as $date) {
            $day = $date->format('Y-m-d');
            $salesByDay[$day] = 0;
        }
        $sales = Sale::where('agent_id', $agentId)
            ->whereBetween('sale_date', [$start30, $end30])
            ->get(['sale_date', 'amount']);
        foreach ($sales as $sale) {
            $day = Carbon::parse($sale->sale_date)->format('Y-m-d');
            if (isset($salesByDay[$day])) {
                $salesByDay[$day] += $sale->amount;
            }
        }

        // 3. 90-day referrals bar chart + conversion line
        $period = CarbonPeriod::create($start90, $end90);
        $referralsByDay = [];
        $conversionsByDay = [];
        foreach ($period as $date) {
            $day = $date->format('Y-m-d');
            $referralsByDay[$day] = 0;
            $conversionsByDay[$day] = 0;
        }
        $referrals = Referral::where('referrer_id', $agentId)
            ->whereBetween('created_at', [$start90, $end90])
            ->get(['created_at']);
        foreach ($referrals as $ref) {
            $day = Carbon::parse($ref->created_at)->format('Y-m-d');
            if (isset($referralsByDay[$day])) {
                $referralsByDay[$day]++;
            }
        }
        $sales90 = Sale::where('agent_id', $agentId)
            ->whereBetween('sale_date', [$start90, $end90])
            ->get(['sale_date']);
        foreach ($sales90 as $sale) {
            $day = Carbon::parse($sale->sale_date)->format('Y-m-d');
            if (isset($conversionsByDay[$day])) {
                $conversionsByDay[$day]++;
            }
        }
        // Conversion rate by day
        $conversionRateByDay = [];
        foreach ($referralsByDay as $day => $refCount) {
            $conversionRateByDay[$day] = $refCount > 0 ? ($conversionsByDay[$day] / $refCount) * 100 : 0;
        }

        // 4. Recent sales table (last 10)
        $recentSales = Sale::where('agent_id', $agentId)
            ->orderByDesc('sale_date')
            ->take(10)
            ->with('commission')
            ->get();

        // 5. Performance summary
        $avgSaleValue = Sale::where('agent_id', $agentId)
            ->avg('amount');
        $bestDay = Sale::where('agent_id', $agentId)
            ->selectRaw('DAYNAME(sale_date) as day, SUM(amount) as total')
            ->groupBy('day')
            ->orderByDesc('total')
            ->first();
        $totalPayouts = Payout::where('agent_id', $agentId)
            ->where('status', 'completed')
            ->sum('amount');
        $pendingPayouts = Payout::where('agent_id', $agentId)
            ->where('status', 'pending')
            ->sum('amount');

        return Inertia::render('Partner/Dashboard', [
            'agent' => [
                'status' => $agent->status,
                'agent_role' => $agent->agent_role,
                'fee_payment_status' => $agent->fee_payment_status,
                'registered_at' => $agent->registered_at?->toDateString(),
                'expires_at' => $agent->expires_at?->toDateString(),
                'renewal_due_at' => $agent->renewal_due_at?->toDateString(),
                'subordinate_count' => $subordinateCount,
            ],
            'stats' => [
                'salesThisMonth' => $salesThisMonth,
                'salesChange' => $salesChange,
                'commThisMonth' => $commThisMonth,
                'commChange' => $commChange,
                'commOwnSalesThisMonth' => $ownSalesTotal,
                'commOverrideThisMonth' => $overrideTotal,
                'referrals90' => $referrals90,
                'refChange' => $refChange,
                'conversionRate' => $conversionRate,
                'conversionChange' => $conversionChange,
                'subordinateCount' => $subordinateCount,
            ],
            'salesByDay' => $salesByDay,
            'referralsByDay' => $referralsByDay,
            'conversionRateByDay' => $conversionRateByDay,
            'recentSales' => $recentSales,
            'performance' => [
                'avgSaleValue' => $avgSaleValue,
                'bestDay' => $bestDay ? $bestDay->day : null,
                'totalPayouts' => $totalPayouts,
                'pendingPayouts' => $pendingPayouts,
            ],
        ]);
    }
}
