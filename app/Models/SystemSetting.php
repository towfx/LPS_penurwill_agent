<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SystemSetting extends Model
{
    use HasFactory, SoftDeletes;

    public const RATE_KEYS = [
        'agent_own_sales',
        'agent_leader_own_sales',
        'agent_leader_override_agent',
        'business_partner_own_sales',
        'business_partner_override_agent',
        'business_partner_override_agent_leader',
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        $casts = [
            'skip_zero_commissions' => 'boolean',
            'reversal_time_limit' => 'integer',
            'email_verification_max_retry' => 'integer',
            'renewal_reminder_days_before' => 'integer',
            'membership_duration_days' => 'integer',
            'renewal_fee_leader_enabled' => 'boolean',
            'renewal_fee_agent_enabled' => 'boolean',
            'commission_fixed_amount' => 'decimal:2',
            'partner_commission_fixed_amount' => 'decimal:2',
            'min_payout_amount' => 'decimal:2',
        ];

        foreach (self::RATE_KEYS as $key) {
            $casts["{$key}_percentage"] = 'decimal:2';
            $casts["{$key}_fixed_amount"] = 'decimal:2';
        }

        foreach (['business_partner', 'leader', 'agent'] as $role) {
            $casts["entry_fee_{$role}"] = 'decimal:2';
            $casts["renewal_fee_{$role}"] = 'decimal:2';
        }

        return $casts;
    }

    /**
     * Build a structured commission config array used by services.
     *
     * @return array<string, array{percentage: float, fixed: float}>
     */
    public function getCommissionConfigAttribute(): array
    {
        $out = [];
        foreach (self::RATE_KEYS as $key) {
            $out[$key] = [
                'percentage' => (float) ($this->{"{$key}_percentage"} ?? 0),
                'fixed' => (float) ($this->{"{$key}_fixed_amount"} ?? 0),
            ];
        }
        return $out;
    }
}
