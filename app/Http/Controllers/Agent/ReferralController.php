<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\AgentVisit;
use App\Models\Sale;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReferralController extends Controller
{
    public function index(Request $request)
    {
        $agent = auth()->user()->agents()->first();
        if (! $agent) {
            return redirect()->route('agent.dashboard');
        }

        $referralCode = $agent->referralCode;
        $startDate = $request->get('start_date', now()->subDays(30)->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());

        $visitsQuery = AgentVisit::where('agent_id', $agent->id)
            ->whereBetween('visit_time', [$startDate.' 00:00:00', $endDate.' 23:59:59']);

        if ($request->filled('converted')) {
            $visitsQuery->when($request->converted === 'yes', function ($q) use ($agent) {
                $q->whereExists(function ($sub) use ($agent) {
                    $sub->from('sales')
                        ->where('sales.agent_id', $agent->id)
                        ->whereColumn('sales.referral_code_id', 'agent_visits.referral_code');
                });
            }, function ($q) use ($agent) {
                $q->whereNotExists(function ($sub) use ($agent) {
                    $sub->from('sales')
                        ->where('sales.agent_id', $agent->id)
                        ->whereColumn('sales.referral_code_id', 'agent_visits.referral_code');
                });
            });
        }

        $visits = $visitsQuery->orderByDesc('visit_time')->paginate(25);

        $totalVisits = AgentVisit::where('agent_id', $agent->id)
            ->whereBetween('visit_time', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
            ->count();

        $convertedVisits = Sale::where('agent_id', $agent->id)
            ->whereBetween('created_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
            ->count();

        $conversionRate = $totalVisits > 0
            ? round(($convertedVisits / $totalVisits) * 100, 1)
            : 0;

        return Inertia::render('Agent/Referral', [
            'referralCode' => $referralCode,
            'visits' => $visits,
            'stats' => [
                'total_visits' => $totalVisits,
                'converted_visits' => $convertedVisits,
                'conversion_rate' => $conversionRate,
            ],
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'converted' => $request->get('converted'),
            ],
        ]);
    }
}
