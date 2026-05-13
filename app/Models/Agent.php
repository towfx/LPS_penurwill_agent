<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Agent extends Model
{
    use HasFactory;

    public const ROLE_AGENT = 'agent';
    public const ROLE_AGENT_LEADER = 'agent_leader';
    public const ROLE_BUSINESS_PARTNER = 'business_partner';

    public const FEE_STATUS_PENDING = 'pending';
    public const FEE_STATUS_PAID = 'paid';
    public const FEE_STATUS_OVERDUE = 'overdue';
    public const FEE_STATUS_WAIVED = 'waived';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'individual_name',
        'individual_phone',
        'individual_email',
        'individual_address',
        'individual_id_number',
        'individual_id_file',
        'company_representative_name',
        'company_representative_id_number',
        'company_representative_id_file',
        'company_name',
        'company_registration_number',
        'company_address',
        'company_phone',
        'company_email_address',
        'company_reg_file',
        'profile_type',
        'referral_code_id',
        'partner_id',
        'status',
        'profile_image',
        'about',
        // Hierarchy + lifecycle
        'agent_role',
        'parent_agent_id',
        'is_default',
        'registered_at',
        'expires_at',
        'renewal_due_at',
        'fee_payment_status',
        // Phase 7 — registration + status fields
        'tc_accepted_at',
        'first_login_at',
        'suspension_reason',
        'rejection_reason',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'status' => 'string',
            'agent_role' => 'string',
            'fee_payment_status' => 'string',
            'is_default' => 'boolean',
            'registered_at' => 'date',
            'expires_at' => 'date',
            'renewal_due_at' => 'date',
            'tc_accepted_at' => 'datetime',
            'first_login_at' => 'datetime',
        ];
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'agents_users');
    }

    public function referralCode()
    {
        return $this->belongsTo(ReferralCode::class);
    }

    public function bankAccount()
    {
        return $this->hasOne(BankAccount::class);
    }

    public function referrals()
    {
        return $this->hasMany(Referral::class, 'referrer_id');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function commissions()
    {
        return $this->hasMany(Commission::class);
    }

    /**
     * Commissions earned by this agent (own sales + overrides).
     */
    public function earnedCommissions()
    {
        return $this->hasMany(Commission::class, 'earning_agent_id');
    }

    public function payouts()
    {
        return $this->hasMany(Payout::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * Custom commission rate rows (one per kind).
     */
    public function commissionRates()
    {
        return $this->hasMany(AgentCommissionRate::class);
    }

    /**
     * @deprecated Use commissionRates(); kept for legacy callers.
     */
    public function commissionRate()
    {
        return $this->hasOne(AgentCommissionRate::class);
    }

    public function referredAgents()
    {
        return $this->hasMany(Agent::class, 'referral_code_id');
    }

    public function visits()
    {
        return $this->hasMany(AgentVisit::class);
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * Direct upline agent.
     */
    public function parentAgent()
    {
        return $this->belongsTo(Agent::class, 'parent_agent_id');
    }

    /**
     * Direct downline agents.
     */
    public function subordinates()
    {
        return $this->hasMany(Agent::class, 'parent_agent_id');
    }

    /**
     * Recursive descendant collection (eager + iterative).
     */
    public function descendants()
    {
        $all = collect();
        $stack = $this->subordinates()->get()->all();
        while ($stack) {
            $current = array_shift($stack);
            $all->push($current);
            foreach ($current->subordinates()->get() as $child) {
                $stack[] = $child;
            }
        }
        return $all;
    }

    public function feePayments()
    {
        return $this->hasMany(FeePayment::class);
    }

    public function notifications()
    {
        return $this->hasMany(AgentNotification::class);
    }

    public function isFirstLogin(): bool
    {
        return $this->first_login_at === null;
    }

    public function getNameAttribute()
    {
        return $this->profile_type === 'company'
            ? $this->company_name
            : $this->individual_name;
    }

    public function getTypeAttribute()
    {
        return $this->profile_type;
    }

    public function getRoleAttribute()
    {
        return $this->agent_role ?? self::ROLE_AGENT;
    }

    public function isLeader(): bool
    {
        return $this->agent_role === self::ROLE_AGENT_LEADER;
    }

    public function isBusinessPartner(): bool
    {
        return $this->agent_role === self::ROLE_BUSINESS_PARTNER;
    }

    public function scopeRole($query, string $role)
    {
        return $query->where('agent_role', $role);
    }

    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_agent_id');
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now()->toDateString(), now()->addDays($days)->toDateString()]);
    }

    public static function generateReferralCode(): string
    {
        $systemSetting = SystemSetting::first();
        $prefix = $systemSetting?->referral_code_prefix ?? 'REF';

        do {
            $code = $prefix.strtoupper(Str::random(8));
            $exists = ReferralCode::where('code', $code)->exists();
        } while ($exists);

        return $code;
    }

    public function createReferralCode(?float $commissionRate = null, ?bool $isActive = true, ?\DateTime $expiresAt = null): ReferralCode
    {
        $systemSetting = SystemSetting::first();

        $referralCode = ReferralCode::create([
            'agent_id' => $this->id,
            'code' => self::generateReferralCode(),
            'is_active' => $isActive ?? true,
            'commission_rate' => $commissionRate ?? $systemSetting?->agent_own_sales_percentage ?? $systemSetting?->commission_default_rate ?? 0,
            'used_count' => 0,
            'expires_at' => $expiresAt ?? now()->addYears(5),
        ]);

        $this->update(['referral_code_id' => $referralCode->id]);

        return $referralCode;
    }
}
