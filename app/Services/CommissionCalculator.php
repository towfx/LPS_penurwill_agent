<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\ReferralCode;

class CommissionCalculator
{
    /**
     * Get the applicable commission rate for an agent.
     *
     * Priority (Phase 0): AgentCommissionRate → system default 10%.
     * Phase 2 will extend this to resolve by $kind against SystemSetting role-based rates.
     *
     * @return array{rate: float, type: string, source: string}
     */
    public function getApplicableRate(Agent $agent, ?ReferralCode $referralCode = null): array
    {
        $commissionRate = $agent->commissionRate;

        if ($commissionRate) {
            return [
                'rate' => (float) $commissionRate->custom_rate,
                'type' => 'percentage',
                'source' => 'agent_rate',
            ];
        }

        return [
            'rate' => 10.0,
            'type' => 'percentage',
            'source' => 'system_default',
        ];
    }

    /**
     * Calculate commission amount from a sale amount, rate, and type.
     *
     * @param  float  $type  'percentage' or 'fixed_amount'
     */
    public function calculate(float $saleAmount, float $rate, string $type = 'percentage'): float
    {
        if ($type === 'fixed_amount') {
            return $rate;
        }

        return ($saleAmount * $rate) / 100;
    }
}
