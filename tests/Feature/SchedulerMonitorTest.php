<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SchedulerMonitorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        SystemSetting::create(['referral_code_prefix' => 'TEST']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'agent', 'guard_name' => 'web']);

        if (! Schema::hasTable('scheduler_logs')) {
            Schema::create('scheduler_logs', function ($table) {
                $table->id();
                $table->string('job_type');
                $table->enum('status', ['success', 'failed'])->default('success');
                $table->string('error_message')->nullable();
                $table->timestamp('ran_at');
                $table->timestamps();
            });
        }
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('scheduler_logs');
        parent::tearDown();
    }

    private function makeAdminUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        return $user;
    }

    /** @test */
    public function fresh_log_entry_shows_ok_status_on_dashboard(): void
    {
        DB::table('scheduler_logs')->insert([
            'job_type' => 'process_renewals',
            'status' => 'success',
            'ran_at' => now()->toDateTimeString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $admin = $this->makeAdminUser();
        $response = $this->actingAs($admin)->get('/admin/dashboard');
        $response->assertOk();
        $response->assertInertia(fn ($page) =>
            $page->where('schedulerAlerts.0.state', 'ok')
        );
    }

    /** @test */
    public function log_older_than_24h_shows_stale_warning(): void
    {
        DB::table('scheduler_logs')->insert([
            'job_type' => 'process_renewals',
            'status' => 'success',
            'ran_at' => now()->subHours(25)->toDateTimeString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $admin = $this->makeAdminUser();
        $response = $this->actingAs($admin)->get('/admin/dashboard');
        $response->assertOk();
        $response->assertInertia(fn ($page) =>
            $page->where('schedulerAlerts.0.state', 'stale')
        );
    }

    /** @test */
    public function no_log_row_shows_never_ran_warning(): void
    {
        // No rows inserted — table is empty
        $admin = $this->makeAdminUser();
        $response = $this->actingAs($admin)->get('/admin/dashboard');
        $response->assertOk();
        $response->assertInertia(fn ($page) =>
            $page->where('schedulerAlerts.0.state', 'never_ran')
        );
    }
}
