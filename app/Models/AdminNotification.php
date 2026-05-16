<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminNotification extends Model
{
    use HasFactory;

    public const STATUS_UNREAD = 'unread';
    public const STATUS_READ = 'read';
    public const STATUS_PENDING = 'pending';
    public const STATUS_ARCHIVED = 'archived';

    public const TYPE_AGENT_APPROVED = 'agent_approved';
    public const TYPE_AGENT_REJECTED = 'agent_rejected';
    public const TYPE_AGENT_SUSPENDED = 'agent_suspended';
    public const TYPE_AGENT_EXPIRED = 'agent_expired';
    public const TYPE_AGENT_CREATED = 'agent_created';
    public const TYPE_FEE_PAYMENT = 'fee_payment';
    public const TYPE_COMMISSION_EARNED = 'commission_earned';
    public const TYPE_COMMISSION_REVERSED = 'commission_reversed';
    public const TYPE_PAYOUT_CREATED = 'payout_created';
    public const TYPE_PAYOUT_PAID = 'payout_paid';
    public const TYPE_PAYOUT_CANCELLED = 'payout_cancelled';
    public const TYPE_NEW_TEAM_MEMBER = 'new_team_member';
    public const TYPE_APPEAL_RECEIVED = 'appeal_received';
    public const TYPE_APPROVAL_REQUESTED = 'approval_requested';

    protected $fillable = [
        'user_id',
        'type',
        'subject',
        'body',
        'status',
        'read_at',
        'related_model',
        'related_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'string',
            'read_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnread($query)
    {
        return $query->where('status', self::STATUS_UNREAD);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeArchived($query)
    {
        return $query->where('status', self::STATUS_ARCHIVED);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function markRead(): void
    {
        $this->update(['status' => self::STATUS_READ, 'read_at' => now()]);
    }

    public function archive(): void
    {
        $this->update(['status' => self::STATUS_ARCHIVED]);
    }

    public function markPending(): void
    {
        $this->update(['status' => self::STATUS_PENDING]);
    }
}
