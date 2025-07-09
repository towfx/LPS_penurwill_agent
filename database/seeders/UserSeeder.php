<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Admin User
        $admin = User::create([
            'name' => 'Administrator',
            'email' => 'admin@mail.com',
            'password' => Hash::make('passw123'),
            'email_verified_at' => now(),
        ]);

        // Assign admin role
        $admin->assignRole('admin');

        // Create Agent User
        $agent = User::create([
            'name' => 'Agent User',
            'email' => 'agent@mail.com',
            'password' => Hash::make('passw123'),
            'email_verified_at' => now(),
        ]);

        // Assign agent role
        $agent->assignRole('agent');
    }
}
