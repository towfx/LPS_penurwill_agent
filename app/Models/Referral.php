<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Referral extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'registered_user_id',
        'ip_address',
        'user_agent',
        'referrer_id',
        'referred_email',
        'referred_name',
        'status',
        'conversion_date',
        'landing_page_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'conversion_date' => 'datetime',
        ];
    }

    /**
     * Get the user who was registered through this referral.
     */
    public function registeredUser()
    {
        return $this->belongsTo(User::class, 'registered_user_id');
    }

    /**
     * Get the agent who made this referral.
     */
    public function referrer()
    {
        return $this->belongsTo(Agent::class, 'referrer_id');
    }
}
