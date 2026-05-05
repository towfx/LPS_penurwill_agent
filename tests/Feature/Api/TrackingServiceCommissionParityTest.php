<?php

namespace Tests\Feature\Api;

use App\Models\Agent;
use App\Models\AgentCommissionRate;
use App\Models\Commission;
use App\Models\ReferralCode;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 0 golden test: verifies that the refactored TrackingService +
 * CommissionGenerator produce exactly the same commission records that the
 * legacy inline logic produced, with no behavioral change.
 */
class TrackingServiceCommissionParityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        User::factory()->create([
            'email' => 'system@penurwill.com',
            'name' => 'System',
        ]);
    }

    public function test_creates_exactly_one_own_sales_commission_per_sale(): void
    {
        $agent = Agent::factory()->create(['status' => 'active']);
        $referralCode = ReferralCode::factory()->create([
            'agent_id' => $agent->id,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/agents/track/sale', [
            'referral_code' => $referralCode->code,
            'customer_name' => 'Parity Test Buyer',
            'customer_email' => 'parity@example.com',
            'sale_amount' => 1000.00,
            'product_name' => 'Parity Package',
            'sale_date' => now()->format('Y-m-d'),
        ]);

        $response->assertStatus(201);

        $sale = Sale::first();
        $this->assertNotNull($sale);

        $commissions = Commission::where('sale_id', $sale->id)->get();
        $this->assertCount(1, $commissions, 'Phase 0 must produce exactly one commission per sale');
    }

    public function test_commission_uses_agent_custom_rate_when_present(): void
    {
        $agent = Agent::factory()->create(['status' => 'active']);
        $referralCode = ReferralCode::factory()->create([
            'agent_id' => $agent->id,
            'is_active' => true,
        ]);
        AgentCommissionRate::factory()->create([
            'agent_id' => $agent->id,
            'custom_rate' => 15.0,
        ]);

        $this->postJson('/api/agents/track/sale', [
            'referral_code' => $referralCode->code,
            'customer_name' => 'Rate Test Buyer',
            'customer_email' => 'rate@example.com',
            'sale_amount' => 1000.00,
            'product_name' => 'Rate Package',
            'sale_date' => now()->format('Y-m-d'),
        ])->assertStatus(201);

        $this->assertDatabaseHas('commissions', [
            'agent_id' => $agent->id,
            'commission_source' => 'agent_rate',
            'applied_rate' => 15.0,
            'commission_rate' => 15.0,
            'amount' => 150.00,
            'status' => 'pending',
        ]);
    }

    public function test_commission_falls_back_to_system_default_10_percent(): void
    {
        $agent = Agent::factory()->create(['status' => 'active']);
        $referralCode = ReferralCode::factory()->create([
            'agent_id' => $agent->id,
            'is_active' => true,
        ]);
        // No AgentCommissionRate — expect 10% system default

        $this->postJson('/api/agents/track/sale', [
            'referral_code' => $referralCode->code,
            'customer_name' => 'Default Rate Buyer',
            'customer_email' => 'default@example.com',
            'sale_amount' => 1000.00,
            'product_name' => 'Default Package',
            'sale_date' => now()->format('Y-m-d'),
        ])->assertStatus(201);

        $this->assertDatabaseHas('commissions', [
            'agent_id' => $agent->id,
            'commission_source' => 'system_default',
            'applied_rate' => 10.0,
            'commission_rate' => 10.0,
            'amount' => 100.00,
            'status' => 'pending',
        ]);
    }

    public function test_sale_commission_amount_matches_commission_record_amount(): void
    {
        $agent = Agent::factory()->create(['status' => 'active']);
        $referralCode = ReferralCode::factory()->create([
            'agent_id' => $agent->id,
            'is_active' => true,
        ]);
        AgentCommissionRate::factory()->create([
            'agent_id' => $agent->id,
            'custom_rate' => 20.0,
        ]);

        $this->postJson('/api/agents/track/sale', [
            'referral_code' => $referralCode->code,
            'customer_name' => 'Sync Test Buyer',
            'customer_email' => 'sync@example.com',
            'sale_amount' => 500.00,
            'product_name' => 'Sync Package',
            'sale_date' => now()->format('Y-m-d'),
        ])->assertStatus(201);

        $sale = Sale::first();
        $commission = Commission::where('sale_id', $sale->id)->first();

        $this->assertEquals(
            (float) $sale->commission_amount,
            (float) $commission->amount,
            'Sale.commission_amount must match Commission.amount'
        );
        $this->assertEquals(100.00, (float) $commission->amount);
    }
}
