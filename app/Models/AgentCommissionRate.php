<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentCommissionRate extends Model
{
    use HasFactory;

    public const KIND_OWN_SALES = 'own_sales';
    public const KIND_OVERRIDE_AGENT = 'override_agent';
    public const KIND_OVERRIDE_AGENT_LEADER = 'override_agent_leader';

    public const CALC_PERCENTAGE = 'percentage';
    public const CALC_FIXED = 'fixed';

    protected $fillable = [
        'agent_id',
        'kind',
        'custom_percentage',
        'custom_fixed_amount',
        'commission_calc_type',
        'effective_from',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'custom_percentage' => 'decimal:2',
            'custom_fixed_amount' => 'decimal:2',
            'effective_from' => 'date',
            'kind' => 'string',
            'commission_calc_type' => 'string',
        ];
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    public function scopeForKind($query, string $kind)
    {
        return $query->where('kind', $kind);
    }

    /**
     * Backwards-compat accessor for legacy `custom_rate` reads.
     */
    public function getCustomRateAttribute()
    {
        return $this->attributes['custom_percentage']
            ?? $this->attributes['custom_rate']
            ?? null;
    }
}
