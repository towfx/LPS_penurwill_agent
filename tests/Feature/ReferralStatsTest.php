<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AgentVisit;
use App\Models\ReferralCode;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferralStatsTest extends TestCase
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

    private function makeAgentWithCode(): array
    {
        $user = User::factory()->create();
        $user->assignRole('agent');
        $agent = Agent::create([
            'profile_type' => 'individual',
            'individual_name' => 'Stats Agent',
            'status' => 'active',
            'agent_role' => Agent::ROLE_AGENT,
            'fee_payment_status' => Agent::FEE_STATUS_PAID,
        ]);
        $user->agents()->attach($agent->id);
        $code = ReferralCode::create([
            'agent_id' => $agent->id,
            'code' => 'TESTCODE1',
            'is_active' => true,
            'commission_rate' => 10,
        ]);
        return [$user, $agent, $code];
    }

    private function makeVisit(Agent $agent, ReferralCode $code, array $attrs = []): AgentVisit
    {
        return AgentVisit::create(array_merge([
            'agent_id' => $agent->id,
            'referral_code' => $code->code,
            'ip_address' => '1.2.3.' . rand(1, 254),
            'visit_url' => 'https://example.com/',
            'visit_time' => now(),
        ], $attrs));
    }

    /** @test */
    public function visits_with_no_sales_return_zero_conversion_rate(): void
    {
        [$user, $agent, $code] = $this->makeAgentWithCode();

        $this->makeVisit($agent, $code);
        $this->makeVisit($agent, $code);

        $response = $this->actingAs($user)->get('/agent/referral');
        $response->assertOk();
        $response->assertInertia(fn ($page) =>
            $page->where('stats.total_visits', 2)
                 ->where('stats.converted_visits', 0)
                 ->where('stats.conversion_rate', 0)
        );
    }

    /** @test */
    public function stats_endpoint_returns_expected_structure(): void
    {
        [$user, $agent, $code] = $this->makeAgentWithCode();
        $this->makeVisit($agent, $code);

        $response = $this->actingAs($user)->get('/agent/referral');
        $response->assertOk();
        $response->assertInertia(fn ($page) =>
            $page->has('stats.total_visits')
                 ->has('stats.converted_visits')
                 ->has('stats.conversion_rate')
                 ->has('visits')
                 ->has('referralCode')
                 ->has('filters')
        );
    }

    /** @test */
    public function date_range_filter_only_counts_visits_in_range(): void
    {
        [$user, $agent, $code] = $this->makeAgentWithCode();

        // Old visit (outside range)
        $this->makeVisit($agent, $code, ['visit_time' => now()->subDays(40)]);

        // Recent visit (in range)
        $this->makeVisit($agent, $code, ['visit_time' => now()->subDays(5)]);

        $response = $this->actingAs($user)->get('/agent/referral?' . http_build_query([
            'start_date' => now()->subDays(30)->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) =>
            $page->where('stats.total_visits', 1)
        );
    }
}
