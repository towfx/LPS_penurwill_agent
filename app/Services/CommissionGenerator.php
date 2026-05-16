<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Agent;
use App\Models\AgentNotification;
use App\Models\Commission;
use App\Models\Sale;
use App\Models\SystemSetting;
use App\Support\SystemUser;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Generates commission rows for a Sale.
 *
 * Phase 0 behavior: produces ONE own_sales commission per sale (parity with legacy).
 * Phase 2 behavior: walks the AgentHierarchy management chain to also create override
 * commissions for the parent agent_leader and business_partner.
 */
class CommissionGenerator
{
    public function __construct(
        protected CommissionCalculator $calculator,
        protected ?AgentHierarchy $hierarchy = null,
    ) {}

    /**
     * Generate all commission rows for a given sale.
     *
     * @return Collection<int, Commission>
     */
    public function generateForSale(Sale $sale): Collection
    {
        return DB::transaction(function () use ($sale) {
            $created = collect();

            $sourceAgent = $sale->agent;
            if (! $sourceAgent) {
                return $created;
            }

            // 1) Own sales commission for the selling agent
            $own = $this->createCommission(
                sale: $sale,
                earningAgent: $sourceAgent,
                kind: CommissionCalculator::KIND_OWN_SALES,
                commissionType: 'own_sales',
                category: $this->categoryForRole($sourceAgent->agent_role ?? 'agent'),
            );
            if ($own) {
                $created->push($own);
            }

            // 2) Override commissions up the hierarchy chain (Phase 2 onwards)
            if ($this->hierarchy && Schema::hasColumn('commissions', 'earning_agent_id')) {
                $chain = $this->hierarchy->getManagementChain($sourceAgent);
                foreach ($chain as $upline) {
                    $kind = $this->overrideKindFor($sourceAgent->agent_role ?? 'agent', $upline->agent_role ?? 'agent');
                    if (! $kind) {
                        continue;
                    }
                    $override = $this->createCommission(
                        sale: $sale,
                        earningAgent: $upline,
                        kind: $kind,
                        commissionType: 'override',
                        category: $this->categoryForRole($upline->agent_role ?? 'agent'),
                    );
                    if ($override) {
                        $created->push($override);
                    }
                }
            }

            return $created;
        });
    }

    /**
     * Create a single commission row given a sale, earning agent, and kind.
     */
    protected function createCommission(Sale $sale, Agent $earningAgent, string $kind, string $commissionType, ?string $category): ?Commission
    {
        $rate = $this->calculator->getApplicableRate($earningAgent, $kind);

        $skipZero = $this->shouldSkipZeroCommissions();
        if ($skipZero && (float) $rate['percentage'] === 0.0 && (float) $rate['fixed_amount'] === 0.0) {
            return null;
        }

        $amount = $this->calculator->calculate(
            saleAmount: (float) $sale->amount,
            percentage: (float) $rate['percentage'],
            fixed: (float) $rate['fixed_amount'],
            calcType: $rate['calc_type'],
        );

        $payload = [
            'sale_id' => $sale->id,
            'agent_id' => $earningAgent->id,
            'commission_source' => $rate['source'],
            'applied_rate' => $rate['percentage'],
            'commission_rate' => $rate['percentage'],
            'amount' => $amount,
            'status' => 'pending',
        ];

        // Hierarchy-aware fields (Phase 1+ schema)
        if (Schema::hasColumn('commissions', 'earning_agent_id')) {
            $payload['earning_agent_id'] = $earningAgent->id;
        }
        if (Schema::hasColumn('commissions', 'commission_type')) {
            $payload['commission_type'] = $commissionType;
        }
        if (Schema::hasColumn('commissions', 'commission_category')) {
            $payload['commission_category'] = $category;
        }
        if (Schema::hasColumn('commissions', 'commission_calc_type')) {
            $payload['commission_calc_type'] = $rate['calc_type'];
        }
        if (Schema::hasColumn('commissions', 'commission_fixed_amount')) {
            $payload['commission_fixed_amount'] = $rate['fixed_amount'];
        }
        if (Schema::hasColumn('commissions', 'source_sale_amount')) {
            $payload['source_sale_amount'] = $sale->amount;
        }
        if (Schema::hasColumn('commissions', 'beneficiary_role')) {
            $payload['beneficiary_role'] = $earningAgent->agent_role ?? 'agent';
        }

        $commission = Commission::create($payload);

        $systemUser = SystemUser::resolve();
        if ($systemUser) {
            ActivityLog::logCreate($systemUser, $commission, $commission->toArray());
        }

        try {
            $kindLabel = $commissionType === 'own_sales' ? 'own sale' : "{$kind} override";
            app(NotificationService::class)->notify(
                $earningAgent,
                AgentNotification::TYPE_COMMISSION_EARNED,
                'New Commission Earned',
                "You earned {$amount} from a {$kindLabel} on sale #{$sale->id} (sale amount: {$sale->amount}).",
                Commission::class,
                $commission->id,
            );
        } catch (\Throwable $e) {
            Log::warning('CommissionGenerator: notify failed', [
                'commission_id' => $commission->id,
                'agent_id' => $earningAgent->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $commission;
    }

    /**
     * Generate a non-persisting preview of what commissions would be created
     * for an agent making a sample sale. Used by admin SystemSettings preview.
     */
    public function regenerateConfigPreview(Agent $agent, float $sampleSaleAmount = 1000.0): array
    {
        $preview = [];
        $own = $this->calculator->getApplicableRate($agent, CommissionCalculator::KIND_OWN_SALES);
        $preview[] = [
            'role' => $agent->agent_role ?? 'agent',
            'commission_type' => 'own_sales',
            'kind' => CommissionCalculator::KIND_OWN_SALES,
            'rate_source' => $own['source'],
            'percentage' => $own['percentage'],
            'fixed_amount' => $own['fixed_amount'],
            'calc_type' => $own['calc_type'],
            'amount' => $this->calculator->calculate($sampleSaleAmount, $own['percentage'], $own['fixed_amount'], $own['calc_type']),
        ];

        if ($this->hierarchy) {
            foreach ($this->hierarchy->getManagementChain($agent) as $upline) {
                $kind = $this->overrideKindFor($agent->agent_role ?? 'agent', $upline->agent_role ?? 'agent');
                if (! $kind) {
                    continue;
                }
                $rate = $this->calculator->getApplicableRate($upline, $kind);
                $preview[] = [
                    'role' => $upline->agent_role ?? 'agent',
                    'commission_type' => 'override',
                    'kind' => $kind,
                    'rate_source' => $rate['source'],
                    'percentage' => $rate['percentage'],
                    'fixed_amount' => $rate['fixed_amount'],
                    'calc_type' => $rate['calc_type'],
                    'amount' => $this->calculator->calculate($sampleSaleAmount, $rate['percentage'], $rate['fixed_amount'], $rate['calc_type']),
                ];
            }
        }

        return $preview;
    }

    protected function categoryForRole(string $role): ?string
    {
        return match ($role) {
            'agent' => 'agent',
            'agent_leader' => 'agent_leader',
            'business_partner' => 'business_partner',
            default => null,
        };
    }

    /**
     * Determine which override kind applies for (sourceRole, beneficiaryRole).
     */
    protected function overrideKindFor(string $sourceRole, string $beneficiaryRole): ?string
    {
        return match ([$sourceRole, $beneficiaryRole]) {
            ['agent', 'agent_leader'] => CommissionCalculator::KIND_OVERRIDE_AGENT,
            ['agent', 'business_partner'] => CommissionCalculator::KIND_OVERRIDE_AGENT,
            ['agent_leader', 'business_partner'] => CommissionCalculator::KIND_OVERRIDE_AGENT_LEADER,
            default => null,
        };
    }

    protected function shouldSkipZeroCommissions(): bool
    {
        if (! Schema::hasColumn('system_settings', 'skip_zero_commissions')) {
            return true;
        }
        $settings = SystemSetting::first();
        return (bool) ($settings->skip_zero_commissions ?? true);
    }
}
