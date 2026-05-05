<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeePayment extends Model
{
    use HasFactory;

    public const TYPE_ENTRY = 'entry';
    public const TYPE_RENEWAL = 'renewal';

    public const METHOD_STRIPE = 'stripe';
    public const METHOD_BANK_TRANSFER = 'bank_transfer';
    public const METHOD_MANUAL = 'manual';
    public const METHOD_WAIVED = 'waived';

    protected $fillable = [
        'agent_id',
        'fee_type',
        'role',
        'amount',
        'payment_method',
        'payment_reference',
        'paid_at',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'fee_type' => 'string',
            'payment_method' => 'string',
        ];
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function scopeStripe($query)
    {
        return $query->where('payment_method', self::METHOD_STRIPE);
    }

    public function scopeManual($query)
    {
        return $query->where('payment_method', self::METHOD_MANUAL);
    }

    public function scopeForAgent($query, int $agentId)
    {
        return $query->where('agent_id', $agentId);
    }
}
