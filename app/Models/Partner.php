<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'parent_id',
        'company_name',
        'company_registration_number',
        'company_address',
        'company_phone',
        'company_email',
        'status',
        'code',
        'company_profile_file',
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
            'parent_id' => 'integer',
        ];
    }

    /**
     * Get the parent partner.
     */
    public function parent()
    {
        return $this->belongsTo(Partner::class, 'parent_id');
    }

    /**
     * Get the child partners.
     */
    public function children()
    {
        return $this->hasMany(Partner::class, 'parent_id');
    }

    /**
     * Get the agents managed by this partner.
     */
    public function agents()
    {
        return $this->hasMany(Agent::class);
    }

    /**
     * Get the users linked to this partner.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'partner_users');
    }
}
