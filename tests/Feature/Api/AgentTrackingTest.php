<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Agent;
use App\Models\ReferralCode;
use App\Models\Referral;
use App\Models\Sale;
use App\Models\Commission;
use App\Models\AgentCommissionRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class AgentTrackingTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Create system user for activity logging
        User::factory()->create([
            'email' => 'system@penurwill.com',
            'name' => 'System User'
        ]);
    }

    /** @test */
    public function it_can_track_referral_with_valid_data()
    {
        // Create agent with referral code
        $agent = Agent::factory()->create(['status' => 'active']);
        $referralCode = ReferralCode::factory()->create([
            'agent_id' => $agent->id,
            'is_active' => true
        ]);

        $data = [
            'referral_code' => $referralCode->code,
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'customer_phone' => '1234567890',
            'notes' => 'Test referral',
            'source' => 'website',
            'amount' => 100.00
        ];

        $response = $this->postJson('/api/agents/track/referral', $data);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Referral tracked successfully'
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'referral_id',
                    'agent_name',
                    'customer_name',
                    'status',
                    'tracked_at'
                ]
            ]);

        $this->assertDatabaseHas('referrals', [
            'referrer_id' => $agent->id,
            'referred_email' => 'john@example.com',
            'referred_name' => 'John Doe',
            'status' => 'pending'
        ]);
    }

    /** @test */
    public function it_cannot_track_referral_with_invalid_referral_code()
    {
        $data = [
            'referral_code' => 'INVALID_CODE',
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com'
        ];

        $response = $this->postJson('/api/agents/track/referral', $data);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid or inactive referral code'
            ]);
    }

    /** @test */
    public function it_cannot_track_referral_with_inactive_referral_code()
    {
        $agent = Agent::factory()->create(['status' => 'active']);
        $referralCode = ReferralCode::factory()->create([
            'agent_id' => $agent->id,
            'is_active' => false
        ]);

        $data = [
            'referral_code' => $referralCode->code,
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com'
        ];

        $response = $this->postJson('/api/agents/track/referral', $data);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid or inactive referral code'
            ]);
    }

    /** @test */
    public function it_cannot_track_referral_with_inactive_agent()
    {
        $agent = Agent::factory()->create(['status' => 'inactive']);
        $referralCode = ReferralCode::factory()->create([
            'agent_id' => $agent->id,
            'is_active' => true
        ]);

        $data = [
            'referral_code' => $referralCode->code,
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com'
        ];

        $response = $this->postJson('/api/agents/track/referral', $data);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Agent not found or inactive'
            ]);
    }

    /** @test */
    public function it_cannot_track_duplicate_referral_for_same_customer()
    {
        $agent = Agent::factory()->create(['status' => 'active']);
        $referralCode = ReferralCode::factory()->create([
            'agent_id' => $agent->id,
            'is_active' => true
        ]);

        // Create existing referral via API to ensure it matches controller logic
        $this->postJson('/api/agents/track/referral', [
            'referral_code' => $referralCode->code,
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com'
        ])->assertStatus(201);

        $data = [
            'referral_code' => $referralCode->code,
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com'
        ];

        $response = $this->postJson('/api/agents/track/referral', $data);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'Customer already referred by this agent'
            ]);
    }

    /** @test */
    public function it_validates_required_fields_for_referral_tracking()
    {
        $response = $this->postJson('/api/agents/track/referral', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['referral_code', 'customer_name', 'customer_email']);
    }

    /** @test */
    public function it_can_track_sale_with_valid_data()
    {
        // Create agent with referral code and commission rate
        $agent = Agent::factory()->create(['status' => 'active']);
        $referralCode = ReferralCode::factory()->create([
            'agent_id' => $agent->id,
            'is_active' => true
        ]);
        AgentCommissionRate::factory()->create([
            'agent_id' => $agent->id,
            'custom_rate' => 15.0
        ]);

        $data = [
            'referral_code' => $referralCode->code,
            'customer_name' => 'Jane Doe',
            'customer_email' => 'jane@example.com',
            'customer_phone' => '0987654321',
            'sale_amount' => 1000.00,
            'product_name' => 'Premium Package',
            'sale_date' => now()->format('Y-m-d'),
            'notes' => 'Test sale',
            'source' => 'website'
        ];

        $response = $this->postJson('/api/agents/track/sale', $data);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Sale tracked successfully'
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'sale_id',
                    'commission_id',
                    'agent_name',
                    'customer_name',
                    'sale_amount',
                    'commission_amount',
                    'commission_percentage',
                    'status',
                    'tracked_at'
                ]
            ]);

        $this->assertDatabaseHas('sales', [
            'agent_id' => $agent->id,
            'amount' => 1000.00,
            'commission_amount' => 150.00,
            'sale_date' => now()->format('Y-m-d H:i:s'),
            'buyer_email' => 'jane@example.com',
            'description' => 'Premium Package',
        ]);

        $this->assertDatabaseHas('commissions', [
            'agent_id' => $agent->id,
            'commission_rate' => 15.0,
            'amount' => 150.00,
            'status' => 'pending'
        ]);
    }

    /** @test */
    public function it_uses_default_commission_rate_when_agent_has_no_rate()
    {
        $agent = Agent::factory()->create(['status' => 'active']);
        $referralCode = ReferralCode::factory()->create([
            'agent_id' => $agent->id,
            'is_active' => true
        ]);

        $data = [
            'referral_code' => $referralCode->code,
            'customer_name' => 'Jane Doe',
            'customer_email' => 'jane@example.com',
            'sale_amount' => 1000.00,
            'product_name' => 'Premium Package',
            'sale_date' => now()->format('Y-m-d')
        ];

        $response = $this->postJson('/api/agents/track/sale', $data);

        $response->assertStatus(201);

        $this->assertDatabaseHas('commissions', [
            'agent_id' => $agent->id,
            'commission_rate' => 10.0,
            'amount' => 100.00
        ]);
    }

    /** @test */
    public function it_validates_required_fields_for_sale_tracking()
    {
        $response = $this->postJson('/api/agents/track/sale', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'referral_code',
                'customer_name',
                'customer_email',
                'sale_amount',
                'product_name',
                'sale_date'
            ]);
    }

    /** @test */
    public function it_validates_sale_amount_is_positive()
    {
        $agent = Agent::factory()->create(['status' => 'active']);
        $referralCode = ReferralCode::factory()->create([
            'agent_id' => $agent->id,
            'is_active' => true
        ]);

        $data = [
            'referral_code' => $referralCode->code,
            'customer_name' => 'Jane Doe',
            'customer_email' => 'jane@example.com',
            'sale_amount' => -100.00,
            'product_name' => 'Premium Package',
            'sale_date' => now()->format('Y-m-d')
        ];

        $response = $this->postJson('/api/agents/track/sale', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sale_amount']);
    }

    /** @test */
    public function it_validates_sale_date_is_not_in_future()
    {
        $agent = Agent::factory()->create(['status' => 'active']);
        $referralCode = ReferralCode::factory()->create([
            'agent_id' => $agent->id,
            'is_active' => true
        ]);

        $data = [
            'referral_code' => $referralCode->code,
            'customer_name' => 'Jane Doe',
            'customer_email' => 'jane@example.com',
            'sale_amount' => 1000.00,
            'product_name' => 'Premium Package',
            'sale_date' => now()->addDays(1)->format('Y-m-d')
        ];

        $response = $this->postJson('/api/agents/track/sale', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sale_date']);
    }

    /** @test */
    public function it_can_get_referral_code_info()
    {
        $agent = Agent::factory()->individual()->create([
            'status' => 'active',
            'individual_name' => 'Test Agent',
        ]);
        $referralCode = ReferralCode::factory()->create([
            'agent_id' => $agent->id,
            'is_active' => true,
            'code' => 'TEST123'
        ]);

        $response = $this->getJson('/api/agents/track/code/TEST123');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'referral_code' => 'TEST123',
                    'agent_name' => 'Test Agent',
                    'agent_type' => 'individual',
                    'is_active' => true
                ]
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'referral_code',
                    'agent_name',
                    'agent_type',
                    'is_active',
                    'created_at'
                ]
            ]);
    }

    /** @test */
    public function it_returns_404_for_invalid_referral_code()
    {
        $response = $this->getJson('/api/agents/track/code/INVALID');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Referral code not found or inactive'
            ]);
    }

    /** @test */
    public function it_returns_404_for_inactive_referral_code()
    {
        $agent = Agent::factory()->create(['status' => 'active']);
        $referralCode = ReferralCode::factory()->create([
            'agent_id' => $agent->id,
            'is_active' => false,
            'code' => 'INACTIVE'
        ]);

        $response = $this->getJson('/api/agents/track/code/INACTIVE');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Referral code not found or inactive'
            ]);
    }

    /** @test */
    public function it_returns_404_for_inactive_agent()
    {
        $agent = Agent::factory()->create(['status' => 'inactive']);
        $referralCode = ReferralCode::factory()->create([
            'agent_id' => $agent->id,
            'is_active' => true,
            'code' => 'INACTIVE_AGENT'
        ]);

        $response = $this->getJson('/api/agents/track/code/INACTIVE_AGENT');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Agent not found or inactive'
            ]);
    }
}
