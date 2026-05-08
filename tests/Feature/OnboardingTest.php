<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        SystemSetting::create(['referral_code_prefix' => 'TEST']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'agent', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    }

    private function makeFirstLoginAgent(): array
    {
        $user = User::factory()->create();
        $user->assignRole('agent');
        $agent = Agent::create([
            'profile_type' => 'individual',
            'individual_name' => 'New Agent',
            'status' => 'active',
            'agent_role' => Agent::ROLE_AGENT,
            'fee_payment_status' => 'paid',
            'first_login_at' => null,
        ]);
        $user->agents()->attach($agent->id);
        return [$user, $agent];
    }

    /** @test */
    public function agent_without_first_login_can_access_onboarding_guide(): void
    {
        [$user] = $this->makeFirstLoginAgent();

        $this->actingAs($user)
            ->get('/get-started-guide')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('GetStartedGuide'));
    }

    /** @test */
    public function completing_guide_sets_first_login_at(): void
    {
        [$user, $agent] = $this->makeFirstLoginAgent();

        $this->actingAs($user)
            ->post('/get-started-guide/complete')
            ->assertRedirect();

        $agent->refresh();
        $this->assertNotNull($agent->first_login_at);
    }

    /** @test */
    public function completing_guide_twice_does_not_reset_timestamp(): void
    {
        [$user, $agent] = $this->makeFirstLoginAgent();

        $this->actingAs($user)->post('/get-started-guide/complete');
        $firstTime = $agent->fresh()->first_login_at;

        // Small sleep to ensure timestamps would differ if reset
        sleep(1);

        $this->actingAs($user)->post('/get-started-guide/complete');
        $this->assertEquals($firstTime->toISOString(), $agent->fresh()->first_login_at->toISOString());
    }
}
