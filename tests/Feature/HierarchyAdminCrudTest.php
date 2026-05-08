<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HierarchyAdminCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Storage::fake('local');
        SystemSetting::create(['referral_code_prefix' => 'TEST']);
        User::factory()->create(['email' => 'system@penurwill.com']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'agent', 'guard_name' => 'web']);
    }

    private function makeAdmin(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole('admin');
        return $user;
    }

    private function agentPayload(array $overrides = []): array
    {
        static $seq = 0;
        $seq++;
        return array_merge([
            'profile_type' => 'individual',
            'individual_name' => 'Test Agent',
            'individual_phone' => '0123456789',
            'individual_address' => '123 Main St',
            'individual_id_number' => 'IC123456',
            'about' => 'Test about',
            'status' => 'active',
            'agent_role' => Agent::ROLE_AGENT,
            'user_email' => "testagent{$seq}@example.com",
            'user_password' => 'Password123!',
            'user_password_confirmation' => 'Password123!',
        ], $overrides);
    }

    /** @test */
    public function admin_can_create_agent_with_role_and_parent(): void
    {
        $admin = $this->makeAdmin();
        $leader = Agent::factory()->agentLeader()->create();

        $this->actingAs($admin)
            ->post(route('admin.agents.store'), $this->agentPayload([
                'agent_role' => Agent::ROLE_AGENT,
                'parent_agent_id' => $leader->id,
            ]))
            ->assertRedirect();

        $agent = Agent::where('individual_name', 'Test Agent')->first();
        $this->assertNotNull($agent);
        $this->assertSame(Agent::ROLE_AGENT, $agent->agent_role);
        $this->assertEquals($leader->id, $agent->parent_agent_id);
    }

    /** @test */
    public function admin_can_set_agent_role_to_leader(): void
    {
        $admin = $this->makeAdmin();
        $bp = Agent::factory()->businessPartner()->create();

        $this->actingAs($admin)
            ->post(route('admin.agents.store'), $this->agentPayload([
                'agent_role' => Agent::ROLE_AGENT_LEADER,
                'parent_agent_id' => $bp->id,
            ]))
            ->assertRedirect();

        $agent = Agent::where('individual_name', 'Test Agent')->first();
        $this->assertSame(Agent::ROLE_AGENT_LEADER, $agent->agent_role);
    }

    private function makeIndividualAgent(array $attrs = []): Agent
    {
        return Agent::factory()->create(array_merge([
            'profile_type' => 'individual',
            'individual_name' => 'Individual Agent',
            'individual_phone' => '0123456789',
            'individual_address' => '123 Main St',
            'individual_id_number' => 'IC' . rand(100000, 999999),
        ], $attrs));
    }

    private function updateIndividualPayload(Agent $agent, array $overrides = []): array
    {
        return array_merge([
            'profile_type' => 'individual',
            'individual_name' => $agent->individual_name ?? 'Individual Agent',
            'individual_phone' => $agent->individual_phone ?? '0123456789',
            'individual_address' => $agent->individual_address ?? '123 Main St',
            'individual_id_number' => $agent->individual_id_number ?? 'IC123456',
            'about' => 'Test about',
            'status' => $agent->status ?? 'active',
            'agent_role' => $agent->agent_role ?? Agent::ROLE_AGENT,
        ], $overrides);
    }

    /** @test */
    public function hierarchy_validation_rejects_cycle(): void
    {
        $admin = $this->makeAdmin();
        $leader = $this->makeIndividualAgent(['agent_role' => Agent::ROLE_AGENT_LEADER]);
        $agent = $this->makeIndividualAgent(['agent_role' => Agent::ROLE_AGENT, 'parent_agent_id' => $leader->id]);

        // Try to set agent as parent of leader → would create cycle
        $response = $this->actingAs($admin)
            ->put(route('admin.agents.update.store', $leader->id),
                $this->updateIndividualPayload($leader, [
                    'agent_role' => Agent::ROLE_AGENT_LEADER,
                    'parent_agent_id' => $agent->id,
                ])
            );

        $response->assertSessionHasErrors(['parent_agent_id']);
        $leader->refresh();
        $this->assertNotEquals($agent->id, $leader->parent_agent_id);
    }

    /** @test */
    public function hierarchy_validation_rejects_lower_rank_parent(): void
    {
        $admin = $this->makeAdmin();
        $agentA = $this->makeIndividualAgent(['agent_role' => Agent::ROLE_AGENT]);
        $bp = $this->makeIndividualAgent(['agent_role' => Agent::ROLE_BUSINESS_PARTNER]);

        // Try to set a plain agent as parent of a business partner (role inversion)
        $response = $this->actingAs($admin)
            ->put(route('admin.agents.update.store', $bp->id),
                $this->updateIndividualPayload($bp, [
                    'agent_role' => Agent::ROLE_BUSINESS_PARTNER,
                    'parent_agent_id' => $agentA->id,
                ])
            );

        $response->assertSessionHasErrors(['parent_agent_id']);
    }

    /** @test */
    public function hierarchy_validation_rejects_self_as_parent(): void
    {
        $admin = $this->makeAdmin();
        $agent = $this->makeIndividualAgent(['agent_role' => Agent::ROLE_AGENT_LEADER]);

        $response = $this->actingAs($admin)
            ->put(route('admin.agents.update.store', $agent->id),
                $this->updateIndividualPayload($agent, [
                    'agent_role' => Agent::ROLE_AGENT_LEADER,
                    'parent_agent_id' => $agent->id,
                ])
            );

        $response->assertSessionHasErrors(['parent_agent_id']);
    }
}
