<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'agent_id',
        'account_name',
        'account_number',
        'bank_name',
        'iban',
        'swift_code',
    ];

    /**
     * Get the agent who owns this bank account.
     */
    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }
}
