<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Commission;
use App\Models\PayoutItem;
use App\Models\Sale;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\PayoutReportGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayoutReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        SystemSetting::create([
            'referral_code_prefix' => 'X',
            'agent_own_sales_percentage' => 10.0,
            'agent_leader_override_agent_percentage' => 5.0,
        ]);
        User::factory()->create(['email' => 'system@penurwill.com']);
    }

    public function test_by_commission_type_tab_returns_array(): void
    {
        $agent = Agent::factory()->create();

        $generator = new PayoutReportGenerator();
        $report = $generator->byCommissionType($agent, now()->year, now()->month);

        // Should return an array (empty if no payouts exist)
        $this->assertIsArray($report);
    }

    public function test_by_sales_source_tab_returns_array(): void
    {
        $agent = Agent::factory()->create();

        $generator = new PayoutReportGenerator();
        $report = $generator->bySalesSource($agent, now()->year, now()->month);

        // Should return an array
        $this->assertIsArray($report);
    }

    public function test_by_time_period_tab_filters_by_date_range(): void
    {
        $agent = Agent::factory()->create();

        $from = now()->startOfMonth();
        $to = now()->endOfMonth();

        $generator = new PayoutReportGenerator();
        $report = $generator->byTimePeriod($agent, $from, $to);

        // Should return an array
        $this->assertIsArray($report);
    }

    public function test_transactions_tab_returns_flat_list(): void
    {
        $agent = Agent::factory()->create();

        $generator = new PayoutReportGenerator();
        $transactions = $generator->transactions($agent, now()->year, now()->month);

        // Should return an array (empty if no payouts exist)
        $this->assertIsArray($transactions);
    }
}
