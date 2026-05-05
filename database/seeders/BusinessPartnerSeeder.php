<?php

namespace Database\Seeders;

use App\Models\Agent;
use Illuminate\Database\Seeder;

/**
 * Creates the canonical default Business Partner agent that acts as the
 * fallback upline for any agent that registers without a referral code (QNA-03).
 */
class BusinessPartnerSeeder extends Seeder
{
    public function run(): void
    {
        $bp = Agent::firstOrCreate(
            ['is_default' => true, 'agent_role' => Agent::ROLE_BUSINESS_PARTNER],
            [
                'profile_type' => 'company',
                'company_name' => 'Penurwill Business Partner',
                'company_email_address' => 'bp@penurwill.com',
                'company_phone' => '+60-000-0000',
                'company_address' => 'HQ',
                'company_representative_name' => 'Penurwill HQ',
                'status' => 'active',
                'agent_role' => Agent::ROLE_BUSINESS_PARTNER,
                'is_default' => true,
                'parent_agent_id' => null,
                'registered_at' => now()->toDateString(),
                'expires_at' => now()->addYears(99)->toDateString(),
                'fee_payment_status' => Agent::FEE_STATUS_WAIVED,
            ]
        );

        $this->command?->info('Default Business Partner agent ensured (id='.$bp->id.').');
    }
}
