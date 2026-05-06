<?php

namespace Tests\Feature\Api;

use App\Models\Agent;
use App\Models\Commission;
use App\Models\Sale;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackingServiceCommissionParityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        SystemSetting::create([
            'referral_code_prefix' => 'PENURWILL-',
            'agent_own_sales_percentage' => 10.0,
        ]);
        User::factory()->create(['email' => 'system@penurwill.com']);
    }

    public function test_tracking_service_commission_generation_is_identical_to_legacy(): void
    {
        // Phase 0 golden test: post a sale and verify exactly one own_sales commission
        // with the same applied_rate and amount as the original legacy logic.
        $agent = Agent::factory()->create();
        $code = $agent->createReferralCode(isActive: true);

        $response = $this->postJson('/api/agents/track/sale', [
            'referral_code' => $code->code,
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'sale_amount' => 1000.00,
            'product_name' => 'Test Product',
            'sale_date' => now()->toDateString(),
        ]);

        $response->assertSuccessful();

        // Verify exactly one own_sales commission was created
        $commissions = Commission::where('agent_id', $agent->id)
            ->where('commission_type', Commission::TYPE_OWN_SALES)
            ->get();

        $this->assertCount(1, $commissions);
        $commission = $commissions->first();

        // Commission amount should be 1000 * 10% = 100
        $this->assertEquals(100.00, (float) $commission->amount);
        $this->assertEquals(10.0, (float) $commission->applied_rate);
        $this->assertSame(Commission::TYPE_OWN_SALES, $commission->commission_type);
    }
}
