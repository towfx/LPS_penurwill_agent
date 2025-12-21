<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    use HasFactory;

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
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => 'string',
        ];
    }

    /**
     * Get the users associated with this agent.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'agents_users');
    }

    /**
     * Get the referral code for this agent.
     */
    public function referralCode()
    {
        return $this->belongsTo(ReferralCode::class);
    }

    /**
     * Get the bank account for this agent.
     */
    public function bankAccount()
    {
        return $this->hasOne(BankAccount::class);
    }

    /**
     * Get the referrals made by this agent.
     */
    public function referrals()
    {
        return $this->hasMany(Referral::class, 'referrer_id');
    }

    /**
     * Get the sales made by this agent.
     */
    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Get the commissions for this agent.
     */
    public function commissions()
    {
        return $this->hasMany(Commission::class);
    }

    /**
     * Get the payouts for this agent.
     */
    public function payouts()
    {
        return $this->hasMany(Payout::class);
    }

    /**
     * Get the activity logs for this agent.
     */
    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * Get the commission rate for this agent.
     */
    public function commissionRate()
    {
        return $this->hasOne(AgentCommissionRate::class);
    }

    /**
     * Get the agents referred by this agent's referral code.
     */
    public function referredAgents()
    {
        return $this->hasMany(Agent::class, 'referral_code_id');
    }

    /**
     * Get the visits tracked for this agent.
     */
    public function visits()
    {
        return $this->hasMany(AgentVisit::class);
    }

    /**
     * Get the partner that manages this agent.
     */
    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * Get the agent's display name
     */
    public function getNameAttribute()
    {
        return $this->profile_type === 'company'
            ? $this->company_name
            : $this->individual_name;
    }

    /**
     * Get the agent's type
     */
    public function getTypeAttribute()
    {
        return $this->profile_type;
    }
}
