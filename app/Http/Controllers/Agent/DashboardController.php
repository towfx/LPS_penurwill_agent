<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
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

        // Total commissions this month
        $commThisMonth = Commission::where('agent_id', $agentId)
            ->whereHas('sale', function ($q) use ($startOfMonth, $endOfMonth) {
                $q->whereBetween('sale_date', [$startOfMonth, $endOfMonth]);
            })
            ->sum('amount');
        $commLastMonth = Commission::where('agent_id', $agentId)
            ->whereHas('sale', function ($q) use ($startOfLastMonth, $endOfLastMonth) {
                $q->whereBetween('sale_date', [$startOfLastMonth, $endOfLastMonth]);
            })
            ->sum('amount');
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

        // 2. Monthly sales line chart (current month, by day)
        $daysInMonth = $now->daysInMonth;
        $salesByDay = array_fill(1, $daysInMonth, 0);
        $sales = Sale::where('agent_id', $agentId)
            ->whereBetween('sale_date', [$startOfMonth, $endOfMonth])
            ->get(['sale_date', 'amount']);
        foreach ($sales as $sale) {
            $day = Carbon::parse($sale->sale_date)->day;
            $salesByDay[$day] += $sale->amount;
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

        return Inertia::render('Agent/Dashboard', [
            'agent' => [
                'status' => $agent->status,
            ],
            'stats' => [
                'salesThisMonth' => $salesThisMonth,
                'salesChange' => $salesChange,
                'commThisMonth' => $commThisMonth,
                'commChange' => $commChange,
                'referrals90' => $referrals90,
                'refChange' => $refChange,
                'conversionRate' => $conversionRate,
                'conversionChange' => $conversionChange,
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
