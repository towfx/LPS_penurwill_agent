<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commission extends Model
{
    use HasFactory;

    /** Commission source types (where the rate came from). */
    public const SOURCE_REFERRAL_CODE = 'referral_code';
    public const SOURCE_AGENT_RATE = 'agent_rate';
    public const SOURCE_SYSTEM_DEFAULT = 'system_default';

    /** Commission type — own sale vs override. */
    public const TYPE_OWN_SALES = 'own_sales';
    public const TYPE_OVERRIDE = 'override';

    /** Beneficiary role categories. */
    public const CAT_AGENT = 'agent';
    public const CAT_AGENT_LEADER = 'agent_leader';
    public const CAT_BUSINESS_PARTNER = 'business_partner';

    /** Calculation type. */
    public const CALC_PERCENTAGE = 'percentage';
    public const CALC_FIXED = 'fixed';

    /** Status enum values (post-Phase 1). */
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'commission_source',
        'applied_rate',
        'sale_id',
        'agent_id',
        'earning_agent_id',
        'commission_type',
        'commission_category',
        'commission_calc_type',
        'commission_fixed_amount',
        'source_sale_amount',
        'beneficiary_role',
        'commission_rate',
        'amount',
        'status',
        'paid_at',
        'paid_by',
        'is_reversal',
        'original_commission_id',
    ];

    protected function casts(): array
    {
        return [
            'applied_rate' => 'decimal:2',
            'commission_rate' => 'decimal:2',
            'amount' => 'decimal:2',
            'commission_fixed_amount' => 'decimal:2',
            'source_sale_amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'commission_source' => 'string',
            'commission_type' => 'string',
            'commission_category' => 'string',
            'commission_calc_type' => 'string',
            'is_reversal' => 'boolean',
        ];
    }

    public static function getCommissionSources(): array
    {
        return [
            self::SOURCE_REFERRAL_CODE,
            self::SOURCE_AGENT_RATE,
            self::SOURCE_SYSTEM_DEFAULT,
        ];
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    /**
     * The agent who *earned* this commission (own_sales or override).
     */
    public function earningAgent()
    {
        return $this->belongsTo(Agent::class, 'earning_agent_id');
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function paidBy()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function payoutItems()
    {
        return $this->hasMany(PayoutItem::class);
    }

    public function originalCommission()
    {
        return $this->belongsTo(Commission::class, 'original_commission_id');
    }

    public function reversals()
    {
        return $this->hasMany(Commission::class, 'original_commission_id');
    }

    public function scopeOwnSales($query)
    {
        return $query->where('commission_type', self::TYPE_OWN_SALES);
    }

    public function scopeOverrides($query)
    {
        return $query->where('commission_type', self::TYPE_OVERRIDE);
    }

    public function scopeForEarner($query, int $agentId)
    {
        return $query->where('earning_agent_id', $agentId);
    }

    public function scopeReversals($query)
    {
        return $query->where('is_reversal', true);
    }
}
