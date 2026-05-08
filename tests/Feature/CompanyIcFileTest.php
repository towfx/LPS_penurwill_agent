<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CompanyIcFileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
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

    private function makeCompanyAgent(): Agent
    {
        return Agent::factory()->create([
            'profile_type' => 'company',
            'company_representative_name' => 'Rep Name',
            'company_name' => 'Test Corp',
            'company_registration_number' => 'REG123',
            'company_address' => '123 Corp St',
            'company_phone' => '0123456789',
            'company_email_address' => 'corp@example.com',
            'status' => 'active',
            'agent_role' => Agent::ROLE_AGENT,
        ]);
    }

    /** @test */
    public function admin_can_upload_company_representative_id_file(): void
    {
        $admin = $this->makeAdmin();
        $agent = $this->makeCompanyAgent();
        $file = UploadedFile::fake()->create('company_ic.pdf', 200, 'application/pdf');

        $this->actingAs($admin)
            ->put(route('admin.agents.update.store', $agent->id), [
                'profile_type' => 'company',
                'company_representative_name' => 'Rep Name',
                'company_name' => 'Test Corp',
                'company_registration_number' => 'REG123',
                'company_address' => '123 Corp St',
                'company_phone' => '0123456789',
                'company_email_address' => 'corp@example.com',
                'status' => 'active',
                'agent_role' => Agent::ROLE_AGENT,
                'company_representative_id_file' => $file,
            ])
            ->assertRedirect();

        $agent->refresh();
        $this->assertNotNull($agent->company_representative_id_file);
        Storage::disk('local')->assertExists($agent->company_representative_id_file);
    }

    /** @test */
    public function admin_can_download_company_representative_id_file(): void
    {
        $admin = $this->makeAdmin();
        $agent = $this->makeCompanyAgent();

        $path = "agents/{$agent->id}/test_ic.pdf";
        Storage::disk('local')->put($path, 'fake pdf content');
        $agent->update(['company_representative_id_file' => $path]);

        $this->actingAs($admin)
            ->get(route('admin.agents.file.download', ['id' => $agent->id, 'field' => 'company_representative_id_file']))
            ->assertSuccessful()
            ->assertHeader('content-disposition');
    }

    /** @test */
    public function download_returns_404_for_disallowed_field(): void
    {
        $admin = $this->makeAdmin();
        $agent = $this->makeCompanyAgent();

        $this->actingAs($admin)
            ->get(route('admin.agents.file.download', ['id' => $agent->id, 'field' => 'password']))
            ->assertNotFound();
    }

    /** @test */
    public function download_returns_404_when_file_missing(): void
    {
        $admin = $this->makeAdmin();
        $agent = $this->makeCompanyAgent();
        $agent->update(['company_representative_id_file' => null]);

        $this->actingAs($admin)
            ->get(route('admin.agents.file.download', ['id' => $agent->id, 'field' => 'company_representative_id_file']))
            ->assertNotFound();
    }

    /** @test */
    public function agent_profile_controller_can_upload_company_representative_id_file(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole('agent');
        $agent = $this->makeCompanyAgent();
        $user->agents()->attach($agent->id);

        $file = UploadedFile::fake()->create('my_ic.pdf', 100, 'application/pdf');

        $this->actingAs($user)
            ->put(route('agent.profile.update'), [
                'profile_type' => 'company',
                'company_representative_name' => 'Rep Name',
                'company_name' => 'Test Corp',
                'company_registration_number' => 'REG123',
                'company_address' => '123 Corp St',
                'company_phone' => '0123456789',
                'company_email_address' => 'corp@example.com',
                'bank_account_name' => 'Rep Name',
                'bank_account_number' => '1234567890',
                'bank_name' => 'Test Bank',
                'iban' => '',
                'swift_code' => '',
                'company_representative_id_file' => $file,
            ])
            ->assertRedirect();

        $agent->refresh();
        $this->assertNotNull($agent->company_representative_id_file);
        Storage::disk('local')->assertExists($agent->company_representative_id_file);
    }
}
