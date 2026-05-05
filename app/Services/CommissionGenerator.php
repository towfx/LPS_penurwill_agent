<?php

namespace App\Services;

use App\Models\Commission;
use App\Models\Sale;
use Illuminate\Support\Collection;

class CommissionGenerator
{
    public function __construct(protected CommissionCalculator $calculator) {}

    /**
     * Generate commission records for a sale and return them as a collection.
     *
     * Phase 0: produces exactly one own_sales Commission — identical output to the
     * legacy inline logic in TrackingService. Phase 2 will add hierarchy traversal
     * and override commissions inside this method without touching TrackingService.
     *
     * Must be called inside an existing DB transaction.
     */
    public function generateForSale(Sale $sale): Collection
    {
        $agent = $sale->agent;
        $rateData = $this->calculator->getApplicableRate($agent);
        $amount = $this->calculator->calculate(
            (float) $sale->amount,
            $rateData['rate'],
            $rateData['type']
        );

        $commission = Commission::create([
            'commission_source' => $rateData['source'],
            'applied_rate' => $rateData['rate'],
            'sale_id' => $sale->id,
            'agent_id' => $agent->id,
            'commission_rate' => $rateData['rate'],
            'amount' => $amount,
            'status' => 'pending',
        ]);

        return collect([$commission]);
    }
}
