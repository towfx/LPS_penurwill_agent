<?php

namespace Tests\Feature;

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

class CommissionReversalWindowTest extends TestCase
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
    }

    private function makeSaleWithCommissions(string $createdAt = null): array
    {
        $agent = Agent::factory()->create();
        $sale = Sale::create([
            'agent_id' => $agent->id,
            'amount' => 1000,
            'commission_amount' => 0,
            'sale_date' => $createdAt ?? now(),
            'description' => 'Test sale',
        ]);
        if ($createdAt) {
            $sale->setCreatedAt($createdAt);
            $sale->save();
        }
        $generator = new CommissionGenerator(new CommissionCalculator(), new AgentHierarchy());
        $commissions = $generator->generateForSale($sale);
        return [$agent, $sale, $commissions];
    }

    /** @test */
    public function reversal_succeeds_for_sale_within_window(): void
    {
        [$agent, $sale] = $this->makeSaleWithCommissions();
        // Sale is just created — within 60 days
        $admin = User::factory()->create();

        $service = new RefundService();
        $reversals = $service->reverseSale($sale, $admin);

        $this->assertNotEmpty($reversals);
    }

    /** @test */
    public function reversal_throws_exception_for_sale_older_than_limit(): void
    {
        [$agent, $sale] = $this->makeSaleWithCommissions();
        // Backdate sale beyond the 60-day window
        $sale->created_at = now()->subDays(61);
        $sale->save();

        $admin = User::factory()->create();
        $service = new RefundService();

        $this->expectException(ReversalWindowExpiredException::class);
        $service->reverseSale($sale, $admin);
    }

    /** @test */
    public function no_reversal_rows_created_when_window_expired(): void
    {
        [$agent, $sale] = $this->makeSaleWithCommissions();
        $sale->created_at = now()->subDays(61);
        $sale->save();

        $admin = User::factory()->create();
        $service = new RefundService();
        $reversalCountBefore = Commission::where('is_reversal', true)->count();

        try {
            $service->reverseSale($sale, $admin);
        } catch (ReversalWindowExpiredException $e) {
            // expected
        }

        $this->assertEquals($reversalCountBefore, Commission::where('is_reversal', true)->count());
    }

    /** @test */
    public function reversal_rows_appear_as_negative_in_payout_context(): void
    {
        [$agent, $sale] = $this->makeSaleWithCommissions();
        $admin = User::factory()->create();

        $service = new RefundService();
        $reversals = $service->reverseSale($sale, $admin);

        $netForAgent = Commission::where('agent_id', $agent->id)->sum('amount');
        $this->assertLessThanOrEqual(0, $netForAgent,
            'Net commission after reversal should be zero or negative');
    }

    /** @test */
    public function reversal_window_respects_custom_reversal_time_limit_setting(): void
    {
        // Change the limit to 10 days
        SystemSetting::first()->update(['reversal_time_limit' => 10]);

        [$agent, $sale] = $this->makeSaleWithCommissions();
        $sale->created_at = now()->subDays(11);
        $sale->save();

        $admin = User::factory()->create();
        $service = new RefundService();

        $this->expectException(ReversalWindowExpiredException::class);
        $service->reverseSale($sale, $admin);
    }
}
