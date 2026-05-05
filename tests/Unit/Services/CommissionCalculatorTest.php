<?php

namespace Tests\Unit\Services;

use App\Models\Agent;
use App\Models\AgentCommissionRate;
use App\Models\SystemSetting;
use App\Services\CommissionCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommissionCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculate_percentage(): void
    {
        $calc = new CommissionCalculator();
        $this->assertSame(50.00, $calc->calculate(1000, 5.0, 0, 'percentage'));
    }

    public function test_calculate_fixed(): void
    {
        $calc = new CommissionCalculator();
        $this->assertSame(25.00, $calc->calculate(1000, 5.0, 25.0, 'fixed'));
    }

    public function test_calculate_percentage_plus_fixed_additive(): void
    {
        $calc = new CommissionCalculator();
        // 1000 * 5% + 25 = 75
        $this->assertSame(75.00, $calc->calculate(1000, 5.0, 25.0, 'percentage'));
    }

    public function test_get_applicable_rate_uses_agent_custom_rate(): void
    {
        SystemSetting::create([
            'referral_code_prefix' => 'X',
            'agent_own_sales_percentage' => 10.0,
        ]);
        $agent = Agent::factory()->create();
        AgentCommissionRate::create([
            'agent_id' => $agent->id,
            'kind' => 'own_sales',
            'custom_percentage' => 12.34,
            'effective_from' => now()->toDateString(),
        ]);

        $calc = new CommissionCalculator();
        $rate = $calc->getApplicableRate($agent, 'own_sales');

        $this->assertSame(12.34, $rate['percentage']);
        $this->assertSame('agent_rate', $rate['source']);
    }

    public function test_get_applicable_rate_falls_back_to_system_default(): void
    {
        SystemSetting::create([
            'referral_code_prefix' => 'X',
            'agent_own_sales_percentage' => 7.5,
        ]);
        $agent = Agent::factory()->create(['agent_role' => 'agent']);

        $calc = new CommissionCalculator();
        $rate = $calc->getApplicableRate($agent, 'own_sales');

        $this->assertSame(7.5, $rate['percentage']);
        $this->assertSame('system_default', $rate['source']);
    }
}
