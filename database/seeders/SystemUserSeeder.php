<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\SystemUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SystemUserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => SystemUser::EMAIL],
            [
                'name' => 'System',
                'password' => Hash::make('passw123'),
                'email_verified_at' => now(),
            ]
        );

        if (method_exists($user, 'assignRole') && ! $user->hasAnyRole(['admin'])) {
            try {
                $user->assignRole('admin');
            } catch (\Throwable $e) {
                // Role may not exist yet during certain seeder orderings.
            }
        }

        SystemUser::flush();

        $this->command?->info('System user ensured: '.SystemUser::EMAIL);
    }
}
