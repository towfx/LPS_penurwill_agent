<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SystemUserSeeder extends Seeder
{
    /**
     * Ensure the internal system user used for automated activity logging exists.
     * Idempotent — safe to run multiple times.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'system@penurwill.com'],
            [
                'name' => 'System',
                'password' => Hash::make(bin2hex(random_bytes(32))),
                'email_verified_at' => now(),
            ]
        );
    }
}
