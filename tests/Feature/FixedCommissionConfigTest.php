<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AgentCommissionRate;
use App\Models\Sale;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\AgentHierarchy;
use App\Services\CommissionCalculator;
use App\Services\CommissionGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Test suite for Commission Configuration / Fixed Amount (RM).
 * Covers persistence of `commission_calc_type` + `commission_fixed_amount`
 * on system_settings, validation edges, and calculation accuracy.
 */
class FixedCommissionConfigTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'agent', 'guard_name' => 'web']);

        SystemSetting::create([
            'referral_code_prefix' => 'PENURWILL-',
            'commission_calc_type' => 'percentage',
            'agent_own_sales_percentage' => 10.0,
        ]);
        User::factory()->create(['email' => 'system@penurwill.com']);

        $this->admin = User::factory()->create(['email_verified_at' => now()]);
        $this->admin->assignRole('admin');
    }

    private function basePayload(array $overrides = []): array
    {
        return array_merge([
            'referral_code_prefix' => 'PENURWILL-',
        ], $overrides);
    }

    /** TC1: Core persistence — select Fixed + amount, save, reload, expect 'fixed'. */
    public function test_fixed_calc_type_persists_after_save(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.system-settings.update'), $this->basePayload([
                'commission_calc_type' => 'fixed',
                'commission_fixed_amount' => 100,
            ]))
            ->assertRedirect(route('admin.system-settings'));

        $s = SystemSetting::first();
        $this->assertSame('fixed', $s->commission_calc_type);
        $this->assertEquals(100.00, (float) $s->commission_fixed_amount);
    }

    /** TC2: Toggle multiple times, final 'fixed' wins. */
    public function test_repeated_toggling_persists_final_state(): void
    {
        $sequence = ['percentage', 'fixed', 'percentage', 'fixed'];
        foreach ($sequence as $type) {
            $this->actingAs($this->admin)
                ->put(route('admin.system-settings.update'), $this->basePayload([
                    'commission_calc_type' => $type,
                    'commission_fixed_amount' => 150,
                ]))
                ->assertRedirect(route('admin.system-settings'));
        }

        $s = SystemSetting::first();
        $this->assertSame('fixed', $s->commission_calc_type);
        $this->assertEquals(150.00, (float) $s->commission_fixed_amount);
    }

    /** TC3: Decimal precision preserved (decimal:2 cast). */
    public function test_decimal_precision_is_preserved(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.system-settings.update'), $this->basePayload([
                'commission_calc_type' => 'fixed',
                'commission_fixed_amount' => 100.75,
            ]))
            ->assertRedirect(route('admin.system-settings'));

        $s = SystemSetting::first();
        $this->assertSame('100.75', (string) $s->commission_fixed_amount);
    }

    /** TC4: Zero accepted (validation rule is min:0). */
    public function test_zero_value_is_accepted(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.system-settings.update'), $this->basePayload([
                'commission_calc_type' => 'fixed',
                'commission_fixed_amount' => 0,
            ]))
            ->assertRedirect(route('admin.system-settings'))
            ->assertSessionHasNoErrors();

        $s = SystemSetting::first();
        $this->assertEquals(0.00, (float) $s->commission_fixed_amount);
    }

    /** TC5: Negative input rejected by validation. */
    public function test_negative_value_is_rejected(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.system-settings.update'), $this->basePayload([
                'commission_calc_type' => 'fixed',
                'commission_fixed_amount' => -50,
            ]))
            ->assertSessionHasErrors('commission_fixed_amount');

        $s = SystemSetting::first();
        $this->assertNotEquals(-50, (float) $s->commission_fixed_amount);
    }

    /** TC6: Large value within decimal(10,2) bounds saves intact. */
    public function test_large_value_within_column_bounds(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.system-settings.update'), $this->basePayload([
                'commission_calc_type' => 'fixed',
                'commission_fixed_amount' => 999999.99,
            ]))
            ->assertRedirect(route('admin.system-settings'))
            ->assertSessionHasNoErrors();

        $s = SystemSetting::first();
        $this->assertEquals(999999.99, (float) $s->commission_fixed_amount);
    }

    /** TC7: Fixed calc returns the exact fixed amount regardless of sale price. */
    public function test_fixed_commission_amount_is_independent_of_sale_amount(): void
    {
        $calc = new CommissionCalculator();
        foreach ([0.01, 500, 1000, 5000, 25000, 999999.99] as $saleAmount) {
            $this->assertSame(
                100.00,
                $calc->calculate((float) $saleAmount, percentage: 0.0, fixed: 100.0, calcType: 'fixed'),
                "Fixed commission must stay 100.00 for sale {$saleAmount}"
            );
        }
    }

    /** TC8: Revert from fixed back to percentage updates both fields. */
    public function test_revert_fixed_to_percentage(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.system-settings.update'), $this->basePayload([
                'commission_calc_type' => 'fixed',
                'commission_fixed_amount' => 200,
            ]));

        $this->assertSame('fixed', SystemSetting::first()->commission_calc_type);

        $this->actingAs($this->admin)
            ->put(route('admin.system-settings.update'), $this->basePayload([
                'commission_calc_type' => 'percentage',
                'agent_own_sales_percentage' => 12.5,
            ]))
            ->assertRedirect(route('admin.system-settings'));

        $s = SystemSetting::first();
        $this->assertSame('percentage', $s->commission_calc_type);
        $this->assertEquals(12.5, (float) $s->agent_own_sales_percentage);

        $agent = Agent::factory()->create();
        $sale = Sale::create([
            'agent_id' => $agent->id,
            'amount' => 1000,
            'commission_amount' => 0,
            'sale_date' => now(),
            'description' => 'Revert check',
        ]);
        $generator = new CommissionGenerator(new CommissionCalculator(), new AgentHierarchy());
        $commissions = $generator->generateForSale($sale);
        $this->assertEquals(125.00, (float) $commissions[0]->amount);
    }
}
