<?php

namespace Tests\Unit\Services;

use App\Models\Agent;
use App\Models\FeePayment;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\FeeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeeServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_apply_entry_fee_creates_payment_and_sets_dates(): void
    {
        SystemSetting::create([
            'referral_code_prefix' => 'X',
            'entry_fee_agent' => 100.00,
            'membership_duration_days' => 365,
            'renewal_reminder_days_before' => 30,
        ]);
        $admin = User::factory()->create();
        $agent = Agent::factory()->create(['agent_role' => 'agent']);

        $service = new FeeService();
        $payment = $service->applyEntryFee($agent, $admin);

        $this->assertSame(FeePayment::TYPE_ENTRY, $payment->fee_type);
        $this->assertEquals(100.00, (float) $payment->amount);

        $agent->refresh();
        $this->assertEquals(Agent::FEE_STATUS_PAID, $agent->fee_payment_status);
        $this->assertNotNull($agent->expires_at);
    }

    public function test_apply_renewal_fee_extends_expiry(): void
    {
        SystemSetting::create([
            'referral_code_prefix' => 'X',
            'renewal_fee_agent' => 100.00,
            'membership_duration_days' => 365,
            'renewal_reminder_days_before' => 30,
        ]);
        $admin = User::factory()->create();
        $agent = Agent::factory()->create([
            'agent_role' => 'agent',
            'expires_at' => now()->addDays(10)->toDateString(),
        ]);

        $service = new FeeService();
        $payment = $service->applyRenewalFee($agent, $admin);

        $this->assertSame(FeePayment::TYPE_RENEWAL, $payment->fee_type);
        $agent->refresh();
        // New expiry should be original (+10 days) + 365 days
        $this->assertTrue($agent->expires_at->gt(now()->addDays(360)));
    }
}
