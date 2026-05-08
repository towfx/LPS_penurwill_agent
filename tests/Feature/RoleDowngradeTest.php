<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Sale;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\AgentHierarchy;
use App\Services\CommissionCalculator;
use App\Services\CommissionGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleDowngradeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        SystemSetting::create([
            'referral_code_prefix' => 'TEST',
            'agent_own_sales_percentage' => 10.0,
            'agent_leader_override_agent_percentage' => 5.0,
            'skip_zero_commissions' => true,
        ]);
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

    private function updatePayload(Agent $agent, array $overrides = []): array
    {
        return array_merge([
            'profile_type' => 'individual',
            'individual_name' => $agent->individual_name ?? 'Test Leader',
            'individual_phone' => $agent->individual_phone ?? '0123456789',
            'individual_address' => $agent->individual_address ?? '123 Main St',
            'individual_id_number' => $agent->individual_id_number ?? 'IC123',
            'status' => $agent->status ?? 'active',
            'agent_role' => $agent->agent_role,
        ], $overrides);
    }

    /** @test */
    public function downgrade_with_no_subordinates_succeeds_silently(): void
    {
        $admin = $this->makeAdmin();
        $leader = Agent::factory()->agentLeader()->create([
            'individual_name' => 'Lonely Leader',
            'individual_phone' => '0111111111',
            'individual_address' => '1 Leader St',
            'individual_id_number' => 'IC-LEADER',
        ]);

        $response = $this->actingAs($admin)
            ->put(route('admin.agents.update.store', $leader->id), $this->updatePayload($leader, [
                'agent_role' => Agent::ROLE_AGENT,
            ]));

        // Should redirect (not a JSON 422 warning)
        $response->assertRedirect();

        $leader->refresh();
        $this->assertSame(Agent::ROLE_AGENT, $leader->agent_role);
    }

    /** @test */
    public function downgrade_with_subordinates_returns_warning_without_saving(): void
    {
        $admin = $this->makeAdmin();
        $leader = Agent::factory()->agentLeader()->create([
            'individual_name' => 'Big Leader',
            'individual_phone' => '0111111111',
            'individual_address' => '1 Leader St',
            'individual_id_number' => 'IC-LEADER',
        ]);
        Agent::factory()->under($leader)->create();
        Agent::factory()->under($leader)->create();

        $response = $this->actingAs($admin)
            ->put(route('admin.agents.update.store', $leader->id), $this->updatePayload($leader, [
                'agent_role' => Agent::ROLE_AGENT,
            ]));

        $response->assertStatus(422);
        $json = $response->json();
        $this->assertTrue($json['downgrade_warning']);
        $this->assertEquals(2, $json['subordinate_count']);

        // Role must not have changed
        $leader->refresh();
        $this->assertSame(Agent::ROLE_AGENT_LEADER, $leader->agent_role);
    }

    /** @test */
    public function downgrade_proceeds_when_confirm_flag_is_set(): void
    {
        $admin = $this->makeAdmin();
        $leader = Agent::factory()->agentLeader()->create([
            'individual_name' => 'Confirmed Leader',
            'individual_phone' => '0111111111',
            'individual_address' => '1 Leader St',
            'individual_id_number' => 'IC-LEADER',
        ]);
        Agent::factory()->under($leader)->create();

        $response = $this->actingAs($admin)
            ->put(route('admin.agents.update.store', $leader->id), $this->updatePayload($leader, [
                'agent_role' => Agent::ROLE_AGENT,
                'confirm_downgrade' => true,
            ]));

        $response->assertRedirect();

        $leader->refresh();
        $this->assertSame(Agent::ROLE_AGENT, $leader->agent_role);
    }

    /** @test */
    public function after_downgrade_sales_generate_no_override_commission_for_ex_leader(): void
    {
        $leader = Agent::factory()->agentLeader()->create();
        $agent = Agent::factory()->under($leader)->create();

        // Downgrade leader to agent
        $leader->update(['agent_role' => Agent::ROLE_AGENT]);

        $sale = Sale::create([
            'agent_id' => $agent->id,
            'amount' => 1000,
            'commission_amount' => 0,
            'sale_date' => now(),
            'description' => 'Post-downgrade sale',
        ]);

        $generator = new CommissionGenerator(new CommissionCalculator(), new AgentHierarchy());
        $commissions = $generator->generateForSale($sale);

        // Only the selling agent should have a commission (no override for ex-leader)
        $overrideCount = $commissions->where('commission_type', 'override')->count();
        $this->assertEquals(0, $overrideCount,
            'Downgraded agent should not generate override commissions');
    }
}
