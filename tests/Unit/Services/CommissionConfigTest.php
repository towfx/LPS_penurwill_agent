<?php

namespace Tests\Unit\Services;

use App\Models\SystemSetting;
use App\Services\CommissionConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CommissionConfigTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_get_rate_for_agent_own_sales(): void
    {
        SystemSetting::create([
            'referral_code_prefix' => 'X',
            'agent_own_sales_percentage' => 10.0,
            'agent_own_sales_fixed_amount' => 0.0,
        ]);

        $config = new CommissionConfig();
        $rate = $config->getRateFor('agent', 'agent', 'own_sales');

        $this->assertEquals(10.0, $rate['percentage']);
        $this->assertEquals(0.0, $rate['fixed']);
    }

    public function test_get_rate_for_agent_leader_override(): void
    {
        SystemSetting::create([
            'referral_code_prefix' => 'X',
            'agent_leader_override_agent_percentage' => 5.0,
            'agent_leader_override_agent_fixed_amount' => 0.0,
        ]);

        $config = new CommissionConfig();
        $rate = $config->getRateFor('agent_leader', 'agent', 'override_agent');

        $this->assertEquals(5.0, $rate['percentage']);
        $this->assertEquals(0.0, $rate['fixed']);
    }

    public function test_get_rate_for_business_partner_override_leader(): void
    {
        SystemSetting::create([
            'referral_code_prefix' => 'X',
            'business_partner_override_agent_leader_percentage' => 3.0,
            'business_partner_override_agent_leader_fixed_amount' => 0.0,
        ]);

        $config = new CommissionConfig();
        $rate = $config->getRateFor('business_partner', 'agent_leader', 'override_agent_leader');

        $this->assertEquals(3.0, $rate['percentage']);
        $this->assertEquals(0.0, $rate['fixed']);
    }

    public function test_flush_clears_cache(): void
    {
        SystemSetting::create([
            'referral_code_prefix' => 'X',
            'agent_own_sales_percentage' => 10.0,
        ]);

        $config = new CommissionConfig();
        // Populate cache
        $config->getRateFor('agent', 'agent', 'own_sales');
        $cacheKey = 'commission_config';
        $this->assertTrue(Cache::has($cacheKey));

        // Flush via service
        $config->flush();
        $this->assertFalse(Cache::has($cacheKey));
    }
}
