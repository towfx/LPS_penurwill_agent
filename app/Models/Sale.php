<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ip_address',
        'user_agent',
        'buyer_email',
        'agent_id',
        'amount',
        'commission_amount',
        'sale_date',
        'description',
        'invoice_number',
        'is_recurring',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'sale_date' => 'date',
            'is_recurring' => 'boolean',
        ];
    }

    /**
     * Get the agent who made this sale.
     */
    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    /**
     * Get the commission for this sale.
     */
    public function commission()
    {
        return $this->hasOne(Commission::class);
    }
}
