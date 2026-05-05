<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Wraps SystemSetting commission_config with a cache layer (QNA-11).
 *
 * Exposes (earnerRole, sourceRole, kind) → ['percentage' => …, 'fixed' => …].
 */
class CommissionConfig
{
    public const CACHE_KEY = 'commission_config';
    public const CACHE_TTL = 3600;

    /**
     * @return array<string, array{percentage: float, fixed: float}>
     */
    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            $settings = SystemSetting::first();
            return $settings?->commission_config ?? [];
        });
    }

    /**
     * Resolve a configured rate by (earner role, source role, kind).
     */
    public function getRateFor(string $earnerRole, string $sourceRole, string $kind): array
    {
        $key = $this->keyFor($earnerRole, $sourceRole, $kind);
        $config = $this->all();
        return $config[$key] ?? ['percentage' => 0.0, 'fixed' => 0.0];
    }

    public function keyFor(string $earnerRole, string $sourceRole, string $kind): ?string
    {
        if ($kind === CommissionCalculator::KIND_OWN_SALES) {
            return match ($earnerRole) {
                'agent' => 'agent_own_sales',
                'agent_leader' => 'agent_leader_own_sales',
                'business_partner' => 'business_partner_own_sales',
                default => null,
            };
        }

        if ($kind === CommissionCalculator::KIND_OVERRIDE_AGENT) {
            return match ($earnerRole) {
                'agent_leader' => 'agent_leader_override_agent',
                'business_partner' => 'business_partner_override_agent',
                default => null,
            };
        }

        if ($kind === CommissionCalculator::KIND_OVERRIDE_AGENT_LEADER) {
            return $earnerRole === 'business_partner' ? 'business_partner_override_agent_leader' : null;
        }

        return null;
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
