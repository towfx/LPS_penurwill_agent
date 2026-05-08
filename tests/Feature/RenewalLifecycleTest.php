<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\FeeService;
use App\Services\RenewalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RenewalLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        SystemSetting::create([
            'referral_code_prefix' => 'TEST',
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

    /** @test */
    public function approval_sets_registered_at_expires_at_and_renewal_due_at(): void
    {
        $admin = User::factory()->create();
        $agent = Agent::factory()->create([
            'status' => 'pending',
            'fee_payment_status' => Agent::FEE_STATUS_PENDING,
        ]);

        $service = new FeeService();
        $service->applyEntryFee($agent, $admin);

        $agent->refresh();
        $this->assertNotNull($agent->registered_at);
        $this->assertNotNull($agent->expires_at);
        $this->assertNotNull($agent->renewal_due_at);

        $expectedExpiry = $agent->registered_at->copy()->addDays(365);
        $this->assertEquals($expectedExpiry->toDateString(), $agent->expires_at->toDateString());

        $expectedReminder = $expectedExpiry->copy()->subDays(30);
        $this->assertEquals($expectedReminder->toDateString(), $agent->renewal_due_at->toDateString());
    }

    /** @test */
    public function renewal_fee_extends_expires_at_by_membership_duration(): void
    {
        $admin = User::factory()->create();
        $agent = Agent::factory()->create([
            'status' => 'active',
            'fee_payment_status' => Agent::FEE_STATUS_PAID,
            'registered_at' => now()->subYear()->toDateString(),
            'expires_at' => now()->toDateString(),
        ]);

        $service = new FeeService();
        $service->applyRenewalFee($agent, $admin);

        $agent->refresh();
        $this->assertSame(Agent::FEE_STATUS_PAID, $agent->fee_payment_status);
        $this->assertNotNull($agent->expires_at);
        // expires_at should be ~365 days from now
        $this->assertGreaterThan(
            now()->addDays(360)->toDateString(),
            $agent->expires_at->toDateString()
        );
    }

    /** @test */
    public function scheduler_marks_expired_agents(): void
    {
        $agent = Agent::factory()->create([
            'status' => 'active',
            'fee_payment_status' => Agent::FEE_STATUS_PENDING,
            'registered_at' => now()->subYear()->subDay()->toDateString(),
            'expires_at' => now()->subDay()->toDateString(),
        ]);

        $service = new RenewalService();
        $count = $service->markExpiredAgents();

        $this->assertEquals(1, $count);
        $agent->refresh();
        $this->assertSame('expired', $agent->status);
    }

    /** @test */
    public function scheduler_does_not_expire_paid_agents(): void
    {
        $agent = Agent::factory()->create([
            'status' => 'active',
            'fee_payment_status' => Agent::FEE_STATUS_PAID,
            'registered_at' => now()->subYear()->subDay()->toDateString(),
            'expires_at' => now()->subDay()->toDateString(),
        ]);

        $service = new RenewalService();
        $count = $service->markExpiredAgents();

        $this->assertEquals(0, $count);
        $agent->refresh();
        $this->assertSame('active', $agent->status);
    }

    /** @test */
    public function scheduler_does_not_expire_agents_whose_expiry_is_in_future(): void
    {
        Agent::factory()->create([
            'status' => 'active',
            'fee_payment_status' => Agent::FEE_STATUS_PENDING,
            'expires_at' => now()->addDay()->toDateString(),
        ]);

        $service = new RenewalService();
        $count = $service->markExpiredAgents();

        $this->assertEquals(0, $count);
    }

    /** @test */
    public function renewal_reminder_sent_on_renewal_due_date(): void
    {
        Mail::fake();

        $agent = Agent::factory()->create([
            'status' => 'active',
            'fee_payment_status' => Agent::FEE_STATUS_PENDING,
            'individual_email' => 'agent@example.com',
            'renewal_due_at' => now()->toDateString(),
            'expires_at' => now()->addDays(30)->toDateString(),
        ]);

        $service = new RenewalService();
        $sent = $service->sendRenewalReminders();

        $this->assertGreaterThanOrEqual(1, $sent);
        Mail::assertSent(\App\Mail\AgentRenewalReminderNotification::class);
    }

    /** @test */
    public function renewal_reminder_not_sent_before_due_date(): void
    {
        Mail::fake();

        Agent::factory()->create([
            'status' => 'active',
            'fee_payment_status' => Agent::FEE_STATUS_PENDING,
            'individual_email' => 'agent@example.com',
            'renewal_due_at' => now()->addDay()->toDateString(),
            'expires_at' => now()->addDays(31)->toDateString(),
        ]);

        $service = new RenewalService();
        $sent = $service->sendRenewalReminders();

        $this->assertEquals(0, $sent);
    }
}
