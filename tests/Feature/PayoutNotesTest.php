<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AgentNotification;
use App\Models\Commission;
use App\Models\Payout;
use App\Models\PayoutItem;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayoutNotesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        SystemSetting::create(['referral_code_prefix' => 'TEST', 'min_payout_amount' => 1.00]);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'agent', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    }

    private function makeAdminUser(): User
    {
        $user = User::factory()->create(['email' => 'admin@test.com', 'email_verified_at' => now()]);
        $user->assignRole('admin');
        return $user;
    }

    private function makeAgentWithPayout(string $agentNote = ''): array
    {
        $user = User::factory()->create();
        $user->assignRole('agent');
        $agent = Agent::create([
            'profile_type' => 'individual',
            'individual_name' => 'Test Agent',
            'status' => 'active',
            'agent_role' => Agent::ROLE_AGENT,
            'fee_payment_status' => 'paid',
        ]);
        $user->agents()->attach($agent->id);

        $payout = Payout::create([
            'agent_id' => $agent->id,
            'amount' => 100.00,
            'status' => 'pending',
            'agent_note' => $agentNote,
        ]);

        return [$agent, $payout, $user];
    }

    /** @test */
    public function agent_note_is_stored_on_payout_creation(): void
    {
        [$agent, $payout] = $this->makeAgentWithPayout('Please process ASAP');

        $this->assertEquals('Please process ASAP', $payout->agent_note);
        $this->assertDatabaseHas('payouts', [
            'id' => $payout->id,
            'agent_note' => 'Please process ASAP',
        ]);
    }

    /** @test */
    public function admin_can_cancel_payout_with_note(): void
    {
        [$agent, $payout] = $this->makeAgentWithPayout();
        $admin = $this->makeAdminUser();

        $this->actingAs($admin)
            ->post("/admin/payout/{$payout->id}/cancel", [
                'admin_note' => 'Duplicate request',
            ])
            ->assertRedirect();

        $payout->refresh();
        $this->assertEquals('cancelled', $payout->status);
        $this->assertEquals('Duplicate request', $payout->admin_note);
    }

    /** @test */
    public function cancel_payout_requires_admin_note(): void
    {
        [$agent, $payout] = $this->makeAgentWithPayout();
        $admin = $this->makeAdminUser();

        $this->actingAs($admin)
            ->post("/admin/payout/{$payout->id}/cancel", [])
            ->assertSessionHasErrors('admin_note');

        $payout->refresh();
        $this->assertNotEquals('cancelled', $payout->status);
    }

    /** @test */
    public function cancel_payout_sends_notification_to_agent(): void
    {
        [$agent, $payout] = $this->makeAgentWithPayout();
        $admin = $this->makeAdminUser();

        $this->actingAs($admin)
            ->post("/admin/payout/{$payout->id}/cancel", ['admin_note' => 'Admin cancelled']);

        $this->assertDatabaseHas('agent_notifications', [
            'agent_id' => $agent->id,
            'type' => AgentNotification::TYPE_PAYOUT_CANCELLED,
        ]);
    }
}
