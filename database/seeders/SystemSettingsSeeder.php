<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class SystemSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'referral_code_prefix' => 'PENURWILL-',
        ];

        // Phase 1+ commission config
        if (Schema::hasColumn('system_settings', 'agent_own_sales_percentage')) {
            $defaults += [
                'agent_own_sales_percentage' => 10.00,
                'agent_own_sales_fixed_amount' => 0,
                'agent_leader_own_sales_percentage' => 10.00,
                'agent_leader_own_sales_fixed_amount' => 0,
                'agent_leader_override_agent_percentage' => 5.00,
                'agent_leader_override_agent_fixed_amount' => 0,
                'business_partner_own_sales_percentage' => 10.00,
                'business_partner_own_sales_fixed_amount' => 0,
                'business_partner_override_agent_percentage' => 2.00,
                'business_partner_override_agent_fixed_amount' => 0,
                'business_partner_override_agent_leader_percentage' => 3.00,
                'business_partner_override_agent_leader_fixed_amount' => 0,
                'skip_zero_commissions' => true,
                'reversal_time_limit' => 60,
                'email_verification_max_retry' => 10,
            ];
        }

        // Fee config + role names
        if (Schema::hasColumn('system_settings', 'entry_fee_business_partner')) {
            $defaults += [
                'entry_fee_business_partner' => 3000.00,
                'renewal_fee_business_partner' => 1000.00,
                'entry_fee_leader' => 100.00,
                'renewal_fee_leader' => 100.00,
                'renewal_fee_leader_enabled' => true,
                'entry_fee_agent' => 100.00,
                'renewal_fee_agent' => 100.00,
                'renewal_fee_agent_enabled' => true,
                'renewal_reminder_days_before' => 30,
                'membership_duration_days' => 365,
                'role_name_agent' => 'Agent',
                'role_name_leader' => 'Leader',
                'role_name_business_partner' => 'Business Partner',
            ];
        }

        // Phase 7 fields
        if (Schema::hasColumn('system_settings', 'min_payout_amount')) {
            $defaults += [
                'min_payout_amount' => 1.00,
            ];
        }

        // Calc-type fields (legacy global columns)
        if (Schema::hasColumn('system_settings', 'commission_calc_type')) {
            $defaults += [
                'commission_calc_type' => 'percentage',
                'partner_commission_calc_type' => 'percentage',
            ];
        }

        // Per-rate-key calc_type columns
        if (Schema::hasColumn('system_settings', 'agent_own_sales_calc_type')) {
            $defaults += [
                'agent_own_sales_calc_type' => 'percentage',
                'agent_leader_own_sales_calc_type' => 'percentage',
                'agent_leader_override_agent_calc_type' => 'percentage',
                'business_partner_own_sales_calc_type' => 'percentage',
                'business_partner_override_agent_calc_type' => 'percentage',
                'business_partner_override_agent_leader_calc_type' => 'percentage',
            ];
        }

        // Legacy columns still present (only used pre-Phase 1 schema)
        if (Schema::hasColumn('system_settings', 'commission_default_rate')) {
            $defaults += ['commission_default_rate' => 10.00];
        }
        if (Schema::hasColumn('system_settings', 'partner_default_commission_rate')) {
            $defaults += ['partner_default_commission_rate' => 0.00];
        }

        SystemSetting::updateOrCreate(['id' => 1], $defaults);

        $this->command?->info('System settings seeded with commission + fee defaults.');
    }
}
