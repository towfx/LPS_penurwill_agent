<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferralCode extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'expires_at',
        'code',
        'agent_id',
        'is_active',
        'commission_rate',
        'used_count',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
            'commission_rate' => 'decimal:2',
            'used_count' => 'integer',
        ];
    }

    /**
     * Get the agent who owns this referral code.
     */
    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    /**
     * Get the agents who were referred using this code.
     */
    public function referredAgents()
    {
        return $this->hasMany(Agent::class);
    }
}
