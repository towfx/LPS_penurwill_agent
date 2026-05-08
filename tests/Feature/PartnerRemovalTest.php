<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\ReferralCode;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PartnerRemovalTest extends TestCase
{
    use RefreshDatabase;

    private Agent $defaultBp;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        SystemSetting::create(['referral_code_prefix' => 'TEST']);
        User::factory()->create(['email' => 'system@penurwill.com']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'agent', 'guard_name' => 'web']);

        $this->defaultBp = Agent::create([
            'profile_type' => 'company',
            'company_name' => 'Default BP',
            'status' => 'active',
            'agent_role' => Agent::ROLE_BUSINESS_PARTNER,
            'is_default' => true,
            'fee_payment_status' => Agent::FEE_STATUS_WAIVED,
        ]);
    }

    private function registerPayload(array $overrides = []): array
    {
        return array_merge([
            'email' => 'newagent@example.com',
            'profile_type' => 'individual',
            'individual_name' => 'New Agent',
            'individual_phone' => '0123456789',
            'individual_address' => '1 Test Street',
            'individual_id_number' => 'IC123456',
            'individual_id_file' => UploadedFile::fake()->create('id.pdf', 100, 'application/pdf'),
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ], $overrides);
    }

    /** @test */
    public function registration_without_referral_code_falls_back_to_default_bp(): void
    {
        $response = $this->post(route('register-as-agent.store'), $this->registerPayload());

        $response->assertSessionDoesntHaveErrors();

        $agent = Agent::where('individual_name', 'New Agent')->first();
        $this->assertNotNull($agent, 'Agent was not created');
        $this->assertEquals($this->defaultBp->id, $agent->parent_agent_id);
    }

    /** @test */
    public function registration_with_valid_referral_code_uses_that_agent_as_parent(): void
    {
        $uplineAgent = Agent::factory()->agentLeader()->create();
        $code = ReferralCode::create([
            'agent_id' => $uplineAgent->id,
            'code' => 'MYCODE123',
            'is_active' => true,
            'used_count' => 0,
            'commission_rate' => 10.0,
        ]);

        $response = $this->post(route('register-as-agent.store'), $this->registerPayload([
            'referral_code' => $code->code,
        ]));

        $response->assertSessionDoesntHaveErrors();

        $agent = Agent::where('individual_name', 'New Agent')->first();
        $this->assertNotNull($agent);
        $this->assertEquals($uplineAgent->id, $agent->parent_agent_id);
    }

    /** @test */
    public function registration_with_nonexistent_referral_code_falls_back_to_default_bp(): void
    {
        // Create a fresh registration without any referral code to confirm BP fallback
        $response = $this->post(route('register-as-agent.store'), $this->registerPayload([
            'email' => 'another@example.com',
        ]));

        $response->assertSessionDoesntHaveErrors();

        $agent = Agent::where('individual_name', 'New Agent')->first();
        $this->assertNotNull($agent);
        $this->assertEquals($this->defaultBp->id, $agent->parent_agent_id,
            'Agent should be under the default BP when no referral code given');
    }
}
