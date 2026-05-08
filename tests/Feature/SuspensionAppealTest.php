<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AgentNotification;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SuspensionAppealTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        SystemSetting::create(['referral_code_prefix' => 'TEST']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'agent', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    }

    private function makeSuspendedAgent(): array
    {
        $user = User::factory()->create();
        $user->assignRole('agent');
        $agent = Agent::create([
            'profile_type' => 'individual',
            'individual_name' => 'Suspended Agent',
            'status' => 'suspended',
            'suspension_reason' => 'Policy violation',
            'agent_role' => Agent::ROLE_AGENT,
            'fee_payment_status' => 'paid',
        ]);
        $user->agents()->attach($agent->id);
        return [$user, $agent];
    }

    /** @test */
    public function suspended_agent_can_submit_appeal(): void
    {
        Mail::fake();
        [$user, $agent] = $this->makeSuspendedAgent();

        $this->actingAs($user)
            ->post('/agent/appeal-suspension', [
                'message' => 'I believe this was a mistake.',
            ])
            ->assertRedirect();

        // Notification created for agent
        $this->assertDatabaseHas('agent_notifications', [
            'agent_id' => $agent->id,
            'type' => AgentNotification::TYPE_APPEAL_RECEIVED,
        ]);
    }

    /** @test */
    public function appeal_requires_message(): void
    {
        [$user, $agent] = $this->makeSuspendedAgent();

        $this->actingAs($user)
            ->post('/agent/appeal-suspension', ['message' => ''])
            ->assertSessionHasErrors('message');
    }

    /** @test */
    public function rejected_agent_can_request_approval(): void
    {
        $user = User::factory()->create();
        $user->assignRole('agent');
        $agent = Agent::create([
            'profile_type' => 'individual',
            'individual_name' => 'Rejected Agent',
            'status' => 'rejected',
            'rejection_reason' => 'Incomplete documents',
            'agent_role' => Agent::ROLE_AGENT,
            'fee_payment_status' => Agent::FEE_STATUS_PENDING,
        ]);
        $user->agents()->attach($agent->id);

        $this->actingAs($user)
            ->post('/agent/request-approval')
            ->assertRedirect();

        $agent->refresh();
        $this->assertEquals('pending', $agent->status);
    }
}
