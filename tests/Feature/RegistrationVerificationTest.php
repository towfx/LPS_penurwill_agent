<?php

namespace Tests\Feature;

use App\Models\RegistrationVerification;
use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        SystemSetting::create(['referral_code_prefix' => 'TEST', 'email_verification_max_retry' => 5]);
    }

    /** @test */
    public function expired_code_is_rejected(): void
    {
        RegistrationVerification::create([
            'email' => 'test@example.com',
            'code' => '123456',
            'expires_at' => now()->subMinute(),
            'verified' => false,
        ]);

        $response = $this->post('/register-as-agent/verify-email', [
            'email' => 'test@example.com',
            'code' => '123456',
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function wrong_code_increments_attempts(): void
    {
        $verification = RegistrationVerification::create([
            'email' => 'test2@example.com',
            'code' => '999999',
            'expires_at' => now()->addMinutes(15),
            'verified' => false,
            'attempts' => 0,
        ]);

        $this->post('/register-as-agent/verify-email', [
            'email' => 'test2@example.com',
            'code' => '000000',
        ]);

        $this->assertGreaterThan(0, $verification->fresh()->attempts);
    }

    /** @test */
    public function fresh_resend_succeeds_and_creates_new_code(): void
    {
        Mail::fake();

        $response = $this->post('/register-as-agent/resend-code', ['email' => 'fresh@example.com']);
        $response->assertOk();

        $this->assertDatabaseHas('registration_verifications', ['email' => 'fresh@example.com']);
    }

    /** @test */
    public function daily_limit_blocks_resend_after_max_retries(): void
    {
        Mail::fake();

        $email = 'limit@example.com';
        $maxRetry = SystemSetting::first()->email_verification_max_retry ?? 5;

        for ($i = 0; $i < $maxRetry; $i++) {
            RegistrationVerification::create([
                'email' => $email,
                'code' => str_pad($i, 6, '0', STR_PAD_LEFT),
                'expires_at' => now()->addMinutes(15),
                'verified' => false,
                'attempts' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $response = $this->post('/register-as-agent/resend-code', ['email' => $email]);
        $response->assertStatus(422);
    }

    /** @test */
    public function resend_creates_a_new_verification_record(): void
    {
        Mail::fake();

        RegistrationVerification::create([
            'email' => 'resend@example.com',
            'code' => '000001',
            'expires_at' => now()->addMinutes(15),
            'verified' => false,
        ]);

        $countBefore = RegistrationVerification::where('email', 'resend@example.com')->count();
        $this->post('/register-as-agent/resend-code', ['email' => 'resend@example.com']);
        $countAfter = RegistrationVerification::where('email', 'resend@example.com')->count();

        $this->assertGreaterThan($countBefore, $countAfter);
    }
}
