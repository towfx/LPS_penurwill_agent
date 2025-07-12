<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Request;

/**
 * ActivityLog Model
 *
 * Helper usage:
 *
 * // Fluent interface (user must be supplied by controller)
 * $log = ActivityLog::createInstance()
 *     ->setUser($user)  // REQUIRED: Controller must supply user
 *     ->setAction('created')
 *     ->setTarget($agent)
 *     ->setAfter($agent->toArray())
 *     ->setDescription("Created new agent: {$agent->name}");
 * // Will be auto-saved at end of request (see AppServiceProvider)
 *
 * // Quick helpers (user must be supplied by controller)
 * ActivityLog::logCreate($user, $agent, $agent->toArray());
 * ActivityLog::logUpdate($user, $agent, $before, $after);
 * ActivityLog::logDelete($user, $agent, $before);
 * ActivityLog::logCustom($user, 'custom_action', 'Description', $target);
 */
class ActivityLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'actor_type', 'actor_role_id', 'before_data', 'after_data',
        'user_id', 'action', 'description', 'ip_address',
        'user_agent', 'target_id', 'target_type',
    ];

    protected $casts = [
        'before_data' => 'array',
        'after_data' => 'array',
    ];

    // --- Auto-save queue for Option B ---
    protected static array $pendingLogs = [];

    public static function booted()
    {
        // No-op: queue is flushed in AppServiceProvider
    }

    // --- Fluent interface methods ---
    public static function createInstance(): self
    {
        $log = new self();
        $log->setIpAddress(Request::ip());
        $log->setUserAgent(Request::userAgent());
        // Add to pending logs for auto-save
        self::$pendingLogs[] = $log;
        return $log;
    }
    public function setAction(string $action): self { $this->action = $action; return $this; }
    public function setDescription(string $description): self { $this->description = $description; return $this; }
    public function setUser(User $user): self {
        $this->user_id = $user->id;
        $this->setActorType(get_class($user));
        $this->setActorRoleId($user->roles->first()?->id);
        return $this;
    }
    public function setActorType(string $actorType): self { $this->actor_type = $actorType; return $this; }
    public function setActorRoleId($roleId): self { $this->actor_role_id = $roleId; return $this; }
    public function setTarget(Model $target): self { $this->target_id = $target->id; $this->target_type = get_class($target); return $this; }
    public function setTargetById($targetId, $targetType): self { $this->target_id = $targetId; $this->target_type = $targetType; return $this; }
    public function setBefore(array $beforeData): self { $this->before_data = $beforeData; return $this; }
    public function setAfter(array $afterData): self { $this->after_data = $afterData; return $this; }
    public function setIpAddress(?string $ipAddress): self { $this->ip_address = $ipAddress; return $this; }
    public function setUserAgent(?string $userAgent): self { $this->user_agent = $userAgent; return $this; }

    // --- Auto-save all pending logs ---
    public static function savePendingLogs(): void
    {
        foreach (self::$pendingLogs as $log) {
            if (!$log->exists && $log->action && $log->user_id) {
                $log->saveQuietly();
            }
        }
        self::$pendingLogs = [];
    }

    // --- Quick helpers (require user parameter) ---
    public static function logCreate(User $user, Model $target, array $data = []): void
    {
        self::createInstance()
            ->setUser($user)
            ->setAction('created')
            ->setTarget($target)
            ->setAfter($data)
            ->setDescription("Created new {$target->getTable()} record");
    }
    public static function logUpdate(User $user, Model $target, array $beforeData, array $afterData): void
    {
        self::createInstance()
            ->setUser($user)
            ->setAction('updated')
            ->setTarget($target)
            ->setBefore($beforeData)
            ->setAfter($afterData)
            ->setDescription("Updated {$target->getTable()} record");
    }
    public static function logDelete(User $user, Model $target, array $beforeData = []): void
    {
        self::createInstance()
            ->setUser($user)
            ->setAction('deleted')
            ->setTarget($target)
            ->setBefore($beforeData)
            ->setDescription("Deleted {$target->getTable()} record");
    }
    public static function logCustom(User $user, string $action, string $description, ?Model $target = null): void
    {
        $log = self::createInstance()
            ->setUser($user)
            ->setAction($action)
            ->setDescription($description);
        if ($target) {
            $log->setTarget($target);
        }
    }

    /**
     * Get the user who performed this action.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
