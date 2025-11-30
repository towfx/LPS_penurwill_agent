<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Agent;
use App\Models\ReferralCode;
use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class AgentRegistrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Create system settings
        SystemSetting::create([
            'referral_code_prefix' => 'TEST',
            'commission_default_rate' => 10.0,
            'global_referral_usage_limit' => 1000,
        ]);

        // Create system user for activity logging
        User::factory()->create([
            'email' => 'system@penurwill.com',
            'name' => 'System User'
        ]);
    }

    /** @test */
    public function it_can_register_individual_agent_with_transaction_protection()
    {
        $data = [
            'email' => 'john@example.com',
            'profile_type' => 'individual',
            'individual_name' => 'John Doe',
            'individual_phone' => '1234567890',
            'individual_address' => '123 Main St, City, State',
            'password' => 'password123!@#',
            'password_confirmation' => 'password123!@#',
            'terms' => true,
        ];

        $response = $this->post('/register-as-agent', $data);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify user was created
        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
        ]);

        // Verify agent was created
        $this->assertDatabaseHas('agents', [
            'profile_type' => 'individual',
            'individual_name' => 'John Doe',
            'status' => 'active',
        ]);

        // Verify referral code was created
        $this->assertDatabaseHas('referral_codes', [
            'code' => function ($query) {
                return $query->where('code', 'like', 'TEST%');
            },
            'is_active' => true,
        ]);

        // Verify user-agent relationship
        $user = User::where('email', 'john@example.com')->first();
        $agent = Agent::where('individual_name', 'John Doe')->first();
        $this->assertTrue($user->agents->contains($agent));
    }

    /** @test */
    public function it_can_register_company_agent_with_transaction_protection()
    {
        $data = [
            'email' => 'company@example.com',
            'profile_type' => 'company',
            'company_representative_name' => 'Jane Smith',
            'company_name' => 'Example Corp',
            'company_registration_number' => 'REG123456',
            'company_address' => '456 Business Ave, City, State',
            'company_phone' => '0987654321',
            'password' => 'password123!@#',
            'password_confirmation' => 'password123!@#',
            'terms' => true,
        ];

        $response = $this->post('/register-as-agent', $data);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify user was created
        $this->assertDatabaseHas('users', [
            'email' => 'company@example.com',
            'name' => 'Jane Smith',
        ]);

        // Verify agent was created
        $this->assertDatabaseHas('agents', [
            'profile_type' => 'company',
            'company_name' => 'Example Corp',
            'status' => 'active',
        ]);

        // Verify referral code was created
        $this->assertDatabaseHas('referral_codes', [
            'code' => function ($query) {
                return $query->where('code', 'like', 'TEST%');
            },
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_rolls_back_transaction_on_validation_failure()
    {
        $data = [
            'email' => 'invalid-email',
            'profile_type' => 'individual',
            'individual_name' => 'John Doe',
            'individual_phone' => '1234567890',
            'individual_address' => '123 Main St, City, State',
            'password' => 'password123!@#',
            'password_confirmation' => 'password123!@#',
            'terms' => true,
        ];

        $response = $this->post('/register-as-agent', $data);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['email']);

        // Verify no user was created
        $this->assertDatabaseMissing('users', [
            'name' => 'John Doe',
        ]);

        // Verify no agent was created
        $this->assertDatabaseMissing('agents', [
            'individual_name' => 'John Doe',
        ]);

        // Verify no referral code was created
        $this->assertDatabaseMissing('referral_codes', [
            'code' => function ($query) {
                return $query->where('code', 'like', 'TEST%');
            },
        ]);
    }

    /** @test */
    public function it_rolls_back_transaction_on_database_error()
    {
        // This test simulates a database error by trying to create a duplicate email
        $existingUser = User::factory()->create(['email' => 'john@example.com']);

        $data = [
            'email' => 'john@example.com', // Duplicate email
            'profile_type' => 'individual',
            'individual_name' => 'John Doe',
            'individual_phone' => '1234567890',
            'individual_address' => '123 Main St, City, State',
            'password' => 'password123!@#',
            'password_confirmation' => 'password123!@#',
            'terms' => true,
        ];

        $response = $this->post('/register-as-agent', $data);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['email']);

        // Verify no additional user was created
        $this->assertEquals(2, User::count()); // Only system user and existing user

        // Verify no agent was created
        $this->assertDatabaseMissing('agents', [
            'individual_name' => 'John Doe',
        ]);
    }

    /** @test */
    public function it_validates_required_fields_for_individual_agent()
    {
        $data = [
            'email' => 'john@example.com',
            'profile_type' => 'individual',
            // Missing individual_name, individual_phone, individual_address
            'password' => 'password123!@#',
            'password_confirmation' => 'password123!@#',
            'terms' => true,
        ];

        $response = $this->post('/register-as-agent', $data);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['individual_name', 'individual_phone', 'individual_address']);

        // Verify no user was created
        $this->assertDatabaseMissing('users', [
            'email' => 'john@example.com',
        ]);
    }

    /** @test */
    public function it_validates_required_fields_for_company_agent()
    {
        $data = [
            'email' => 'company@example.com',
            'profile_type' => 'company',
            // Missing company fields
            'password' => 'password123!@#',
            'password_confirmation' => 'password123!@#',
            'terms' => true,
        ];

        $response = $this->post('/register-as-agent', $data);

        $response->assertRedirect();
        $response->assertSessionHasErrors([
            'company_representative_name',
            'company_name',
            'company_registration_number',
            'company_address',
            'company_phone'
        ]);

        // Verify no user was created
        $this->assertDatabaseMissing('users', [
            'email' => 'company@example.com',
        ]);
    }
} 