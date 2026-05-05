<?php

namespace App\Support;

use App\Models\User;

/**
 * Resolves the cached "system" user used for activity logging on automated
 * actions (API tracking, scheduled jobs, webhooks).
 */
class SystemUser
{
    public const EMAIL = 'system@penurwill.com';

    protected static ?User $cached = null;

    public static function resolve(): ?User
    {
        if (self::$cached && self::$cached->exists) {
            return self::$cached;
        }

        return self::$cached = User::where('email', self::EMAIL)->first();
    }

    public static function flush(): void
    {
        self::$cached = null;
    }
}
