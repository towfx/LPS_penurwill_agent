<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\RegistrationVerification;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationWizardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        SystemSetting::create([
            'referral_code_prefix' => 'TEST',
            'email_verification_max_retry' => 10,
            'entry_fee_agent' => 100,
            'entry_fee_business_partner' => 3000,
        ]);

        // Default BP upline
        Agent::create([
            'profile_type' => 'company',
            'company_name' => 'Default BP',
            'status' => 'active',
            'agent_role' => Agent::ROLE_BUSINESS_PARTNER,
            'is_default' => true,
            'fee_payment_status' => 'waived',
        ]);
    }

    /** @test */
    public function registration_page_renders_with_fee_amounts(): void
    {
        $response = $this->get('/register-as-agent');
        $response->assertOk();
        $response->assertInertia(fn ($page) =>
            $page->component('RegisterAsAgent')
                 ->has('packages')
        );
    }

    /** @test */
    public function registration_page_passes_company_bank_when_available(): void
    {
        $bpAgent = Agent::first();
        \App\Models\BankAccount::create([
            'agent_id' => $bpAgent->id,
            'bank_name' => 'Maybank',
            'account_name' => 'Test Company',
            'account_number' => '1234567890',
        ]);

        $response = $this->get('/register-as-agent');
        $response->assertOk();
        $response->assertInertia(fn ($page) =>
            $page->has('companyBank')
        );
    }

    /** @test */
    public function email_pre_check_blocks_existing_email_with_password(): void
    {
        User::factory()->create(['email' => 'existing@example.com', 'password' => bcrypt('password'), 'email_verified_at' => now()]);

        $response = $this->post('/get-started/check-email', ['email' => 'existing@example.com']);
        $response->assertJson(['status' => 'login']);
    }

    /** @test */
    public function email_pre_check_flags_unverified_email_as_reset(): void
    {
        User::factory()->unverified()->create(['email' => 'unverified@example.com']);

        $response = $this->post('/get-started/check-email', ['email' => 'unverified@example.com']);
        $response->assertJson(['status' => 'reset']);
    }

    /** @test */
    public function email_pre_check_returns_new_for_unknown_email(): void
    {
        $response = $this->post('/get-started/check-email', ['email' => 'brand-new@example.com']);
        $response->assertJson(['status' => 'new']);
    }

    /** @test */
    public function resend_code_generates_verification_record(): void
    {
        Mail::fake();

        $response = $this->post('/register-as-agent/resend-code', ['email' => 'test@example.com']);
        $response->assertOk();

        $this->assertDatabaseHas('registration_verifications', [
            'email' => 'test@example.com',
        ]);
    }

    /** @test */
    public function verify_email_with_valid_code_returns_success(): void
    {
        Mail::fake();

        $verification = RegistrationVerification::create([
            'email' => 'verify@example.com',
            'code' => '123456',
            'expires_at' => now()->addMinutes(15),
            'verified' => false,
        ]);

        // Create agent+user so verifyEmail can succeed
        $user = User::factory()->create(['email' => 'verify@example.com']);
        $agent = Agent::create([
            'profile_type' => 'individual',
            'individual_name' => 'Test',
            'status' => 'pending',
            'agent_role' => 'agent',
            'fee_payment_status' => Agent::FEE_STATUS_PENDING,
        ]);
        $user->agents()->attach($agent->id);

        $response = $this->post('/register-as-agent/verify-email', [
            'email' => 'verify@example.com',
            'code' => '123456',
        ]);

        $response->assertOk();
    }

    /** @test */
    public function verify_email_with_wrong_code_returns_error(): void
    {
        RegistrationVerification::create([
            'email' => 'wrong@example.com',
            'code' => '654321',
            'expires_at' => now()->addMinutes(15),
            'verified' => false,
        ]);

        $response = $this->post('/register-as-agent/verify-email', [
            'email' => 'wrong@example.com',
            'code' => '000000',
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function verify_email_with_expired_code_returns_error(): void
    {
        RegistrationVerification::create([
            'email' => 'expired@example.com',
            'code' => '111111',
            'expires_at' => now()->subMinutes(5),
            'verified' => false,
        ]);

        $response = $this->post('/register-as-agent/verify-email', [
            'email' => 'expired@example.com',
            'code' => '111111',
        ]);

        $response->assertStatus(422);
    }
}
