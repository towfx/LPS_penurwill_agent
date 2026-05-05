<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayoutItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'payout_id',
        'commission_id',
        'commission_type',
        'commission_category',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function payout()
    {
        return $this->belongsTo(Payout::class);
    }

    public function commission()
    {
        return $this->belongsTo(Commission::class);
    }

    public function scopeOwnSales($query)
    {
        return $query->where('commission_type', 'own_sales');
    }

    public function scopeOverrides($query)
    {
        return $query->where('commission_type', 'override');
    }
}
