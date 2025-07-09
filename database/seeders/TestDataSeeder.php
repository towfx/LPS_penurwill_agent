<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // This seeder can be used for additional test data
        // For example: sample tasks, notifications, etc.

        $this->command->info('Test data seeder completed.');
    }
}
