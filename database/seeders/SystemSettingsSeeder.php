<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create or update system settings
        SystemSetting::updateOrCreate(
            ['id' => 1], // Assuming we want to use ID 1 for the main settings
            [
                'commission_default_rate' => 10.00, // 10%
                'referral_code_prefix' => 'WILL-WRITE-',
                'global_referral_usage_limit' => 100,
            ]
        );

        $this->command->info('System settings created successfully');
        $this->command->info('Default commission rate: 10%');
        $this->command->info('Referral code prefix: WILL-WRITE-');
        $this->command->info('Global referral usage limit: 100');
    }
}
