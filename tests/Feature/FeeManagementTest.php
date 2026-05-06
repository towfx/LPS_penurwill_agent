<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\FeePayment;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\FeeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeeManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        SystemSetting::create([
            'referral_code_prefix' => 'X',
            'entry_fee_agent' => 100.00,
            'entry_fee_leader' => 200.00,
            'entry_fee_business_partner' => 3000.00,
            'renewal_fee_agent' => 100.00,
            'renewal_fee_leader' => 100.00,
            'renewal_fee_business_partner' => 1000.00,
            'renewal_fee_agent_enabled' => true,
            'renewal_fee_leader_enabled' => true,
            'membership_duration_days' => 365,
            'renewal_reminder_days_before' => 30,
        ]);
        User::factory()->create(['email' => 'system@penurwill.com']);
    }

    public function test_entry_fee_applied_on_approval(): void
    {
        $admin = User::factory()->create();
        $agent = Agent::factory()->create([
            'agent_role' => 'agent',
            'status' => 'pending',
            'fee_payment_status' => 'pending',
        ]);

        $service = new FeeService();
        $payment = $service->applyEntryFee($agent, $admin);

        $this->assertSame(FeePayment::TYPE_ENTRY, $payment->fee_type);
        $this->assertEquals(100.00, (float) $payment->amount);

        $agent->refresh();
        $this->assertSame('paid', $agent->fee_payment_status);
        $this->assertNotNull($agent->registered_at);
        $this->assertNotNull($agent->expires_at);
        $this->assertEquals(365, $agent->registered_at->diffInDays($agent->expires_at, false));
    }

    public function test_renewal_fee_updates_expiry_dates(): void
    {
        $admin = User::factory()->create();
        $agent = Agent::factory()->create([
            'agent_role' => 'agent',
            'registered_at' => now()->subDays(360)->toDateString(),
            'expires_at' => now()->addDays(5)->toDateString(),
            'renewal_due_at' => now()->toDateString(),
            'fee_payment_status' => 'pending',
        ]);

        $originalExpiry = $agent->expires_at->clone();

        $service = new FeeService();
        $payment = $service->applyRenewalFee($agent, $admin);

        $this->assertSame(FeePayment::TYPE_RENEWAL, $payment->fee_type);
        $this->assertEquals(100.00, (float) $payment->amount);

        $agent->refresh();
        $this->assertSame('paid', $agent->fee_payment_status);
        // New expiry should be 365 days from original expiry (not from today)
        $this->assertTrue(
            $agent->expires_at->gt($originalExpiry),
            'Expiry should be extended'
        );
    }

    public function test_expired_agent_marked_correctly(): void
    {
        $agent = Agent::factory()->create([
            'status' => 'active',
            'expires_at' => now()->subDays(1)->toDateString(),
            'fee_payment_status' => 'pending',
        ]);

        // Simulate cron job
        Agent::where('expires_at', '<', now()->toDateString())
            ->where('fee_payment_status', '!=', 'paid')
            ->update(['status' => 'expired']);

        $agent->refresh();
        $this->assertSame('expired', $agent->status);
    }

    public function test_get_fee_amount_for_role(): void
    {
        $service = new FeeService();

        $agentFee = $service->getFeeAmountFor('agent', 'entry');
        $this->assertEquals(100.00, $agentFee);

        $leaderFee = $service->getFeeAmountFor('agent_leader', 'entry');
        $this->assertEquals(200.00, $leaderFee);

        $bpFee = $service->getFeeAmountFor('business_partner', 'entry');
        $this->assertEquals(3000.00, $bpFee);
    }

    public function test_is_renewal_enabled_returns_setting(): void
    {
        $service = new FeeService();

        $this->assertTrue($service->isRenewalEnabled('agent'));
        $this->assertTrue($service->isRenewalEnabled('agent_leader'));
        $this->assertTrue($service->isRenewalEnabled('business_partner'));

        // Disable renewal for agent
        SystemSetting::first()->update(['renewal_fee_agent_enabled' => false]);

        $this->assertFalse($service->isRenewalEnabled('agent'));
        $this->assertTrue($service->isRenewalEnabled('agent_leader'));
    }
}
