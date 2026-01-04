<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Call role and permission seeder first
        $this->call([
            RolePermissionSeeder::class,
            SystemSettingsSeeder::class,
            UserSeeder::class,
            PartnerSeeder::class,
            AgentSeeder::class,
            // TestDataSeeder::class, // Generate sales data for agents
        ]);
    }
}
