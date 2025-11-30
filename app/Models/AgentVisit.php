<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentVisit extends Model
{
    protected $fillable = [
        'agent_id',
        'referral_code',
        'visit_url',
        'visit_time',
        'referral_page',
        'session_id',
        'page_title',
        'ip_address',
        'user_agent',
        'screen_resolution',
        'language',
        'timezone',
    ];

    protected $casts = [
        'visit_time' => 'datetime',
    ];

    /**
     * Get the agent that owns the visit
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }
}
