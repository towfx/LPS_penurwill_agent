<?php

namespace Tests\Unit\Services;

use App\Models\Agent;
use App\Services\AgentHierarchy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentHierarchyTest extends TestCase
{
    use RefreshDatabase;

    public function test_management_chain_walks_to_top(): void
    {
        $bp = Agent::factory()->businessPartner()->create();
        $leader = Agent::factory()->agentLeader()->under($bp)->create();
        $agent = Agent::factory()->under($leader)->create();

        $hierarchy = new AgentHierarchy();
        $chain = $hierarchy->getManagementChain($agent);

        $this->assertCount(2, $chain);
        $this->assertSame($leader->id, $chain[0]->id);
        $this->assertSame($bp->id, $chain[1]->id);
    }

    public function test_validate_rejects_self_parent(): void
    {
        $agent = Agent::factory()->create();
        $hierarchy = new AgentHierarchy();
        $errors = $hierarchy->validateHierarchyChange($agent, $agent);
        $this->assertNotEmpty($errors);
    }

    public function test_validate_rejects_role_inversion(): void
    {
        $hierarchy = new AgentHierarchy();
        $bp = Agent::factory()->businessPartner()->create();
        $agent = Agent::factory()->create();
        // Trying to make BP a child of an Agent → role inversion
        $errors = $hierarchy->validateHierarchyChange($bp, $agent);
        $this->assertNotEmpty($errors);
    }

    public function test_would_create_cycle(): void
    {
        $bp = Agent::factory()->businessPartner()->create();
        $leader = Agent::factory()->agentLeader()->under($bp)->create();
        $agent = Agent::factory()->under($leader)->create();

        $hierarchy = new AgentHierarchy();
        // Trying to make $bp a child of $agent → cycle
        $this->assertTrue($hierarchy->wouldCreateCycle($bp, $agent));
    }
}
