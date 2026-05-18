<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentCommissionRate;
use App\Models\Commission;
use App\Models\SystemSetting;

/**
 * Pure commission rate resolver and amount calculator.
 *
 * Resolution priority (Decision 7 update):
 * 1. AgentCommissionRate matched by (agent_id, kind)
 * 2. SystemSetting role-based defaults
 *
 * The ReferralCode commission_rate is no longer in the calculation chain.
 */
class CommissionCalculator
{
    public const KIND_OWN_SALES = 'own_sales';
    public const KIND_OVERRIDE_AGENT = 'override_agent';
    public const KIND_OVERRIDE_AGENT_LEADER = 'override_agent_leader';

    public const CALC_PERCENTAGE = 'percentage';
    public const CALC_FIXED = 'fixed';

    public const SOURCE_AGENT_RATE = 'agent_rate';
    public const SOURCE_SYSTEM_DEFAULT = 'system_default';

    /**
     * Resolve the applicable rate for an agent + kind combination.
     *
     * @return array{percentage: float, fixed_amount: float, calc_type: string, source: string}
     */
    public function getApplicableRate(Agent $agent, string $kind = self::KIND_OWN_SALES): array
    {
        $custom = AgentCommissionRate::where('agent_id', $agent->id)
            ->when(
                \Schema::hasColumn('agent_commission_rates', 'kind'),
                fn ($q) => $q->where('kind', $kind)
            )
            ->first();

        if ($custom) {
            return [
                'percentage' => (float) ($custom->custom_percentage ?? $custom->custom_rate ?? 0),
                'fixed_amount' => (float) ($custom->custom_fixed_amount ?? 0),
                'calc_type' => $custom->commission_calc_type ?? self::CALC_PERCENTAGE,
                'source' => self::SOURCE_AGENT_RATE,
            ];
        }

        return $this->getSystemDefaultRate($agent, $kind);
    }

    /**
     * Get the system default rate for an agent role + kind.
     */
    public function getSystemDefaultRate(Agent $agent, string $kind = self::KIND_OWN_SALES): array
    {
        $settings = SystemSetting::first();
        $role = $agent->agent_role ?? 'agent';

        // Phase 0 fallback: legacy commission_default_rate field
        if ($settings && isset($settings->commission_default_rate) && ! \Schema::hasColumn('system_settings', 'agent_own_sales_percentage')) {
            return [
                'percentage' => (float) $settings->commission_default_rate,
                'fixed_amount' => 0.0,
                'calc_type' => self::CALC_PERCENTAGE,
                'source' => self::SOURCE_SYSTEM_DEFAULT,
            ];
        }

        $key = $this->settingKeyFor($role, $kind);
        if (! $key || ! $settings) {
            return [
                'percentage' => 10.0,
                'fixed_amount' => 0.0,
                'calc_type' => self::CALC_PERCENTAGE,
                'source' => self::SOURCE_SYSTEM_DEFAULT,
            ];
        }

        return [
            'percentage' => (float) ($settings->{"{$key}_percentage"} ?? 0),
            'fixed_amount' => (float) ($settings->{"{$key}_fixed_amount"} ?? 0),
            'calc_type' => $settings->{"{$key}_calc_type"} ?? self::CALC_PERCENTAGE,
            'source' => self::SOURCE_SYSTEM_DEFAULT,
        ];
    }

    /**
     * Map (role, kind) to the SystemSetting key prefix.
     */
    public function settingKeyFor(string $role, string $kind): ?string
    {
        return match ([$role, $kind]) {
            ['agent', self::KIND_OWN_SALES] => 'agent_own_sales',
            ['agent_leader', self::KIND_OWN_SALES] => 'agent_leader_own_sales',
            ['agent_leader', self::KIND_OVERRIDE_AGENT] => 'agent_leader_override_agent',
            ['business_partner', self::KIND_OWN_SALES] => 'business_partner_own_sales',
            ['business_partner', self::KIND_OVERRIDE_AGENT] => 'business_partner_override_agent',
            ['business_partner', self::KIND_OVERRIDE_AGENT_LEADER] => 'business_partner_override_agent_leader',
            default => null,
        };
    }

    /**
     * Compute a commission amount.
     *
     * Either/or semantics (QNA-01 revision 2026-05-18):
     * - calc_type=percentage:  saleAmount * percentage/100 (fixed ignored)
     * - calc_type=fixed:       fixed amount (percentage ignored)
     */
    public function calculate(float $saleAmount, float $percentage, float $fixed = 0.0, string $calcType = self::CALC_PERCENTAGE): float
    {
        if ($calcType === self::CALC_FIXED) {
            return round($fixed, 2);
        }

        return round(($saleAmount * $percentage) / 100.0, 2);
    }
}
