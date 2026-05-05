<?php

namespace Tests\Unit\Services;

use App\Exceptions\ReversalWindowExpiredException;
use App\Models\Agent;
use App\Models\Commission;
use App\Models\Sale;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\AgentHierarchy;
use App\Services\CommissionCalculator;
use App\Services\CommissionGenerator;
use App\Services\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefundServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        SystemSetting::create([
            'referral_code_prefix' => 'X',
            'agent_own_sales_percentage' => 10.0,
            'reversal_time_limit' => 60,
        ]);
        User::factory()->create(['email' => 'system@penurwill.com']);
    }

    public function test_reverse_sale_creates_negative_commission(): void
    {
        $admin = User::factory()->create();
        $agent = Agent::factory()->create();
        $sale = Sale::create([
            'agent_id' => $agent->id,
            'amount' => 1000,
            'commission_amount' => 0,
            'sale_date' => now(),
            'description' => 'Test',
        ]);
        (new CommissionGenerator(new CommissionCalculator(), new AgentHierarchy()))->generateForSale($sale);

        $service = new RefundService();
        $reversals = $service->reverseSale($sale, $admin);

        $this->assertCount(1, $reversals);
        $this->assertSame(-100.00, (float) $reversals[0]->amount);
        $this->assertTrue((bool) $reversals[0]->is_reversal);
        $this->assertSame(Commission::STATUS_CANCELLED, $reversals[0]->status);
    }

    public function test_reversal_outside_window_throws(): void
    {
        $admin = User::factory()->create();
        $agent = Agent::factory()->create();
        $sale = Sale::create([
            'agent_id' => $agent->id,
            'amount' => 1000,
            'commission_amount' => 0,
            'sale_date' => now()->subDays(120),
            'description' => 'Old',
        ]);
        $sale->created_at = now()->subDays(120);
        $sale->save();

        $this->expectException(ReversalWindowExpiredException::class);
        (new RefundService())->reverseSale($sale, $admin);
    }
}
