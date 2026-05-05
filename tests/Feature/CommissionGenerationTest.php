<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Commission;
use App\Models\Sale;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\AgentHierarchy;
use App\Services\CommissionCalculator;
use App\Services\CommissionGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommissionGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        SystemSetting::create([
            'referral_code_prefix' => 'X',
            'agent_own_sales_percentage' => 10.0,
            'agent_leader_override_agent_percentage' => 5.0,
            'business_partner_override_agent_percentage' => 2.0,
            'business_partner_override_agent_leader_percentage' => 3.0,
            'skip_zero_commissions' => true,
        ]);
        User::factory()->create(['email' => 'system@penurwill.com']);
    }

    public function test_agent_only_creates_one_own_sales_commission(): void
    {
        $agent = Agent::factory()->create();
        $sale = Sale::create([
            'agent_id' => $agent->id,
            'amount' => 1000,
            'commission_amount' => 0,
            'sale_date' => now(),
            'description' => 'Test',
        ]);

        $generator = new CommissionGenerator(new CommissionCalculator(), new AgentHierarchy());
        $commissions = $generator->generateForSale($sale);

        $this->assertCount(1, $commissions);
        $this->assertEquals(100.00, (float) $commissions[0]->amount);
        $this->assertSame('own_sales', $commissions[0]->commission_type);
    }

    public function test_agent_with_leader_creates_two_commissions(): void
    {
        $leader = Agent::factory()->agentLeader()->create();
        $agent = Agent::factory()->under($leader)->create();
        $sale = Sale::create([
            'agent_id' => $agent->id,
            'amount' => 1000,
            'commission_amount' => 0,
            'sale_date' => now(),
            'description' => 'Test',
        ]);

        $generator = new CommissionGenerator(new CommissionCalculator(), new AgentHierarchy());
        $commissions = $generator->generateForSale($sale);

        $this->assertCount(2, $commissions);
        $this->assertEquals(100.00, (float) $commissions[0]->amount); // own_sales 10%
        $this->assertEquals(50.00, (float) $commissions[1]->amount);  // leader override 5%
    }

    public function test_agent_with_leader_and_bp_creates_three_commissions(): void
    {
        $bp = Agent::factory()->businessPartner()->create();
        $leader = Agent::factory()->agentLeader()->under($bp)->create();
        $agent = Agent::factory()->under($leader)->create();
        $sale = Sale::create([
            'agent_id' => $agent->id,
            'amount' => 1000,
            'commission_amount' => 0,
            'sale_date' => now(),
            'description' => 'Test',
        ]);

        $generator = new CommissionGenerator(new CommissionCalculator(), new AgentHierarchy());
        $commissions = $generator->generateForSale($sale);

        $this->assertCount(3, $commissions);
        $amounts = $commissions->pluck('amount')->map(fn ($a) => (float) $a)->all();
        $this->assertEqualsCanonicalizing([100.00, 50.00, 20.00], $amounts);
    }

    public function test_skip_zero_commissions_setting_skips_zero_rows(): void
    {
        SystemSetting::query()->update([
            'business_partner_override_agent_percentage' => 0,
            'business_partner_override_agent_fixed_amount' => 0,
        ]);

        $bp = Agent::factory()->businessPartner()->create();
        $agent = Agent::factory()->under($bp)->create();
        $sale = Sale::create([
            'agent_id' => $agent->id,
            'amount' => 1000,
            'commission_amount' => 0,
            'sale_date' => now(),
            'description' => 'Test',
        ]);

        $generator = new CommissionGenerator(new CommissionCalculator(), new AgentHierarchy());
        $commissions = $generator->generateForSale($sale);

        // Only own_sales row remains; BP row skipped because both pct and fixed are zero.
        $this->assertCount(1, $commissions);
        $this->assertSame('own_sales', $commissions[0]->commission_type);
    }
}
