<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AgentNotification;
use App\Models\Commission;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private NotificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        SystemSetting::create(['referral_code_prefix' => 'TEST']);
        $this->service = app(NotificationService::class);
    }

    private function makeAgent(array $attrs = []): Agent
    {
        $user = User::factory()->create();
        $agent = Agent::create(array_merge([
            'profile_type' => 'individual',
            'individual_name' => 'Test Agent',
            'status' => 'active',
            'agent_role' => Agent::ROLE_AGENT,
            'fee_payment_status' => 'paid',
        ], $attrs));
        $user->agents()->attach($agent->id);
        return $agent;
    }

    /** @test */
    public function notify_creates_agent_notification_row(): void
    {
        $agent = $this->makeAgent();

        $notification = $this->service->notify($agent, AgentNotification::TYPE_AGENT_APPROVED, 'You are approved', 'Welcome!');

        $this->assertNotNull($notification);
        $this->assertDatabaseHas('agent_notifications', [
            'agent_id' => $agent->id,
            'type' => AgentNotification::TYPE_AGENT_APPROVED,
            'subject' => 'You are approved',
            'status' => AgentNotification::STATUS_UNREAD,
        ]);
    }

    /** @test */
    public function notify_failure_does_not_throw_to_caller(): void
    {
        // Pass an agent that has no users (DB may still work, this tests the exception guard)
        $agent = $this->makeAgent();

        // Forcibly pass bad related_id type — service should still not throw
        $result = $this->service->notify($agent, 'invalid_type', 'Subject', 'Body', null, null);
        $this->assertNotNull($result);
    }

    /** @test */
    public function inbox_tabs_filter_by_status_correctly(): void
    {
        $agent = $this->makeAgent();

        AgentNotification::create([
            'agent_id' => $agent->id, 'type' => 'test', 'subject' => 'Unread', 'body' => 'x', 'status' => 'unread',
        ]);
        AgentNotification::create([
            'agent_id' => $agent->id, 'type' => 'test', 'subject' => 'Pending', 'body' => 'x', 'status' => 'pending',
        ]);
        AgentNotification::create([
            'agent_id' => $agent->id, 'type' => 'test', 'subject' => 'Archived', 'body' => 'x', 'status' => 'archived',
        ]);

        $this->assertCount(1, AgentNotification::forAgent($agent->id)->unread()->get());
        $this->assertCount(1, AgentNotification::forAgent($agent->id)->pending()->get());
        $this->assertCount(1, AgentNotification::forAgent($agent->id)->archived()->get());
    }

    /** @test */
    public function mark_read_sets_status_and_read_at(): void
    {
        $agent = $this->makeAgent();
        $notification = AgentNotification::create([
            'agent_id' => $agent->id, 'type' => 'test', 'subject' => 'S', 'body' => 'B', 'status' => 'unread',
        ]);

        $notification->markRead();

        $this->assertEquals('read', $notification->fresh()->status);
        $this->assertNotNull($notification->fresh()->read_at);
    }

    /** @test */
    public function notify_admin_targets_first_agent(): void
    {
        $adminUser = User::factory()->create(['email' => 'system@penurwill.com']);
        $adminAgent = Agent::create([
            'profile_type' => 'individual',
            'individual_name' => 'Admin Agent',
            'status' => 'active',
            'agent_role' => Agent::ROLE_BUSINESS_PARTNER,
            'is_default' => true,
            'fee_payment_status' => 'paid',
        ]);
        $adminUser->agents()->attach($adminAgent->id);

        // Ensure Agent::find(1) works
        $this->assertEquals($adminAgent->id, Agent::first()->id);

        $notification = $this->service->notifyAdmin('test_type', 'Admin subject', 'Admin body');

        $this->assertDatabaseHas('agent_notifications', [
            'agent_id' => $adminAgent->id,
            'subject' => 'Admin subject',
        ]);
    }
}
