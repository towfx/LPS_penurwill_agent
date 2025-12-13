<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
     * Track a sale using a referral code
     *
     * @return Sale|null
     */
    public static function trackSale(string $referralCode, array $saleData)
    {
        return DB::transaction(function () use ($referralCode, $saleData) {
            // Find the referral code and validate it
            $referralCodeModel = ReferralCode::where('code', $referralCode)
                ->where('is_active', true)
                ->first();

            if (! $referralCodeModel) {
                throw new \Exception("Invalid or inactive referral code: {$referralCode}");
            }

            // Check if referral code has expired
            if ($referralCodeModel->expires_at && $referralCodeModel->expires_at->isPast()) {
                throw new \Exception("Referral code has expired: {$referralCode}");
            }

            // Get the agent from the referral code
            $agent = $referralCodeModel->agent;
            if (! $agent) {
                throw new \Exception("No agent found for referral code: {$referralCode}");
            }

            // Calculate commission amount
            $commissionAmount = $saleData['amount'] * ($referralCodeModel->commission_rate / 100);

            // Prepare sale data
            $saleData['agent_id'] = $agent->id;
            $saleData['commission_amount'] = $commissionAmount;
            $saleData['sale_date'] = $saleData['sale_date'] ?? now();

            // Create the sale
            $sale = self::create($saleData);

            // Increment the used count for the referral code
            $referralCodeModel->increment('used_count');

            // Create commission record
            Commission::create([
                'agent_id' => $agent->id,
                'sale_id' => $sale->id,
                'amount' => $commissionAmount,
                'commission_rate' => $referralCodeModel->commission_rate,
                'applied_rate' => $referralCodeModel->commission_rate,
                'status' => 'pending',
                'commission_source' => Commission::SOURCE_REFERRAL_CODE,
            ]);

            return $sale;
        });
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
