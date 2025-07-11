<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentCommissionRate extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'agent_id',
        'custom_rate',
        'effective_from',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'custom_rate' => 'decimal:2',
            'effective_from' => 'date',
        ];
    }

    /**
     * Get the agent who has this commission rate.
     */
    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }
}
