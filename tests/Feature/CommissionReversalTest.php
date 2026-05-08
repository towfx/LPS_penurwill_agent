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
use App\Services\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommissionReversalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        SystemSetting::create([
            'referral_code_prefix' => 'TEST',
            'agent_own_sales_percentage' => 10.0,
            'reversal_time_limit' => 60,
            'skip_zero_commissions' => true,
        ]);
        User::factory()->create(['email' => 'system@penurwill.com']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    }

    private function makeSaleWithCommissions(): array
    {
        $agent = Agent::factory()->create();
        $sale = Sale::create([
            'agent_id' => $agent->id,
            'amount' => 1000,
            'commission_amount' => 0,
            'sale_date' => now(),
            'description' => 'Test sale',
        ]);
        $generator = new CommissionGenerator(new CommissionCalculator(), new AgentHierarchy());
        $commissions = $generator->generateForSale($sale);
        return [$agent, $sale, $commissions];
    }

    /** @test */
    public function refund_creates_negative_commission_rows(): void
    {
        [$agent, $sale, $original] = $this->makeSaleWithCommissions();
        $admin = User::factory()->create();

        $service = new RefundService();
        $reversals = $service->reverseSale($sale, $admin);

        $this->assertCount($original->count(), $reversals);
        foreach ($reversals as $reversal) {
            $this->assertTrue((float) $reversal->amount < 0, 'Reversal amount should be negative');
            $this->assertTrue($reversal->is_reversal);
            $this->assertSame(Commission::STATUS_CANCELLED, $reversal->status);
        }
    }

    /** @test */
    public function original_commissions_are_cancelled_after_refund(): void
    {
        [$agent, $sale, $original] = $this->makeSaleWithCommissions();
        $admin = User::factory()->create();

        $service = new RefundService();
        $service->reverseSale($sale, $admin);

        foreach ($original as $commission) {
            $commission->refresh();
            $this->assertSame(Commission::STATUS_CANCELLED, $commission->status);
        }
    }

    /** @test */
    public function reversal_links_to_original_commission_via_original_commission_id(): void
    {
        [$agent, $sale, $original] = $this->makeSaleWithCommissions();
        $admin = User::factory()->create();

        $service = new RefundService();
        $reversals = $service->reverseSale($sale, $admin);

        foreach ($reversals as $reversal) {
            $this->assertNotNull($reversal->original_commission_id);
            $this->assertContains(
                $reversal->original_commission_id,
                $original->pluck('id')->toArray()
            );
        }
    }

    /** @test */
    public function reversal_amount_equals_negative_of_original(): void
    {
        [$agent, $sale, $original] = $this->makeSaleWithCommissions();
        $admin = User::factory()->create();

        $service = new RefundService();
        $reversals = $service->reverseSale($sale, $admin);

        foreach ($reversals as $reversal) {
            $orig = Commission::find($reversal->original_commission_id);
            $this->assertEquals(
                (float) $orig->amount,
                -1 * (float) $reversal->amount,
                'Reversal amount should be negative of original',
                0.001
            );
        }
    }

    /** @test */
    public function refund_via_admin_route_triggers_reversals(): void
    {
        [$agent, $sale] = $this->makeSaleWithCommissions();
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole('admin');

        $commissionsBefore = Commission::where('sale_id', $sale->id)->where('is_reversal', false)->count();

        $this->actingAs($admin)
            ->post(route('admin.sales.refund', $sale))
            ->assertRedirect();

        $reversals = Commission::where('sale_id', $sale->id)->where('is_reversal', true)->count();
        $this->assertEquals($commissionsBefore, $reversals);
    }
}
