<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\AgentVisit;
use App\Models\ReferralCode;
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

        $paginatedVisits = $visitsQuery->orderByDesc('visit_time')->paginate(25)->withQueryString();

        // Build referral-code-to-sale map for conversion enrichment
        $codeValue = $referralCode?->code;
        $salesByCode = $codeValue
            ? Sale::where('agent_id', $agent->id)->whereNotNull('referral_code_id')
                ->whereHas('referralCode', fn ($q) => $q->where('code', $codeValue))
                ->get(['id', 'created_at', 'referral_code_id'])
                ->keyBy('id')
            : collect();

        $saleCreatedAt = $salesByCode->first()?->created_at;

        $enrichedData = $paginatedVisits->getCollection()->map(function ($visit) use ($salesByCode, $saleCreatedAt) {
            $matchedSale = $salesByCode->first(fn ($s) => $s->created_at >= $visit->visit_time);
            $daysToConvert = null;
            if ($matchedSale) {
                $diff = $visit->visit_time->diffInDays($matchedSale->created_at);
                $daysToConvert = $diff;
            }
            return [
                'id' => $visit->id,
                'created_at' => $visit->visit_time,
                'ip_address' => $visit->ip_address,
                'is_converted' => $matchedSale !== null,
                'sale_id' => $matchedSale?->id,
                'days_to_convert' => $daysToConvert,
            ];
        });

        $paginatedVisits->setCollection($enrichedData);

        $totalVisits = AgentVisit::where('agent_id', $agent->id)
            ->whereBetween('visit_time', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
            ->count();

        $convertedVisits = $codeValue
            ? Sale::where('agent_id', $agent->id)
                ->whereBetween('created_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
                ->whereHas('referralCode', fn ($q) => $q->where('code', $codeValue))
                ->count()
            : 0;

        $conversionRate = $totalVisits > 0
            ? round(($convertedVisits / $totalVisits) * 100, 1)
            : 0;

        // Avg days to convert
        $avgDays = null;
        if ($codeValue && $convertedVisits > 0) {
            try {
                $saleTimings = Sale::where('agent_id', $agent->id)
                    ->whereBetween('created_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
                    ->whereHas('referralCode', fn ($q) => $q->where('code', $codeValue))
                    ->get(['created_at']);
                $firstVisit = AgentVisit::where('agent_id', $agent->id)
                    ->where('referral_code', $codeValue)
                    ->orderBy('visit_time')
                    ->first();
                if ($firstVisit && $saleTimings->isNotEmpty()) {
                    $totalDays = $saleTimings->sum(fn ($s) => $firstVisit->visit_time->diffInDays($s->created_at));
                    $avgDays = round($totalDays / $saleTimings->count(), 1);
                }
            } catch (\Throwable $e) {
                // non-critical
            }
        }

        return Inertia::render('Agent/Referral', [
            'referralCode' => $referralCode,
            'visits' => $paginatedVisits,
            'stats' => [
                'total_visits' => $totalVisits,
                'converted_visits' => $convertedVisits,
                'conversion_rate' => $conversionRate,
                'avg_days_to_convert' => $avgDays,
            ],
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'converted' => $request->get('converted'),
            ],
        ]);
    }
}
