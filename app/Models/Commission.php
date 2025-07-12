<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commission extends Model
{
    use HasFactory;

    /**
     * Commission source types
     */
    const SOURCE_REFERRAL_CODE = 'referral_code';
    const SOURCE_AGENT_RATE = 'agent_rate';
    const SOURCE_SYSTEM_DEFAULT = 'system_default';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'commission_source',
        'applied_rate',
        'sale_id',
        'agent_id',
        'commission_rate',
        'amount',
        'status',
        'paid_at',
        'paid_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'applied_rate' => 'decimal:2',
            'commission_rate' => 'decimal:2',
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'commission_source' => 'string', // Enum: 'referral_code', 'agent_rate', 'system_default'
        ];
    }

    /**
     * Get all available commission source types
     *
     * @return array
     */
    public static function getCommissionSources(): array
    {
        return [
            self::SOURCE_REFERRAL_CODE,
            self::SOURCE_AGENT_RATE,
            self::SOURCE_SYSTEM_DEFAULT,
        ];
    }

    /**
     * Get the agent who earned this commission.
     */
    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    /**
     * Get the sale that generated this commission.
     */
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Get the user who marked this commission as paid.
     */
    public function paidBy()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    /**
     * Get the payout items for this commission.
     */
    public function payoutItems()
    {
        return $this->hasMany(PayoutItem::class);
    }
}
