<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\BankAccount;
use App\Models\Referral;
use App\Models\Sale;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\TrackingService;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Creates a verifiable hierarchy that mirrors the requirements example:
 *
 *   BP1  (Business Partner)
 *   ├── AL1  (Agent Leader, parent=BP1)
 *   │   ├── A1  (Agent, parent=AL1)
 *   │   └── A2  (Agent, parent=AL1)
 *   ├── A3   (Agent, parent=BP1 directly)
 *   └── AL2  (Agent Leader, parent=BP1)
 *       └── A4  (Agent, parent=AL2)
 *
 * Each agent gets 5 sales so commission propagation can be verified
 * at every hierarchy level.
 */
class TestDataSeeder extends Seeder
{
    private \Faker\Generator $faker;

    /** @var array<string, string> shortName → email */
    private const AGENTS = [
        'BP1' => ['label' => 'Business Partner One', 'email' => 'bp1@test.com',  'role' => Agent::ROLE_BUSINESS_PARTNER, 'parent' => null],
        'AL1' => ['label' => 'Agent Leader One',     'email' => 'al1@test.com',  'role' => Agent::ROLE_AGENT_LEADER,    'parent' => 'BP1'],
        'AL2' => ['label' => 'Agent Leader Two',     'email' => 'al2@test.com',  'role' => Agent::ROLE_AGENT_LEADER,    'parent' => 'BP1'],
        'A1'  => ['label' => 'Agent One',            'email' => 'a1@test.com',   'role' => Agent::ROLE_AGENT,           'parent' => 'AL1'],
        'A2'  => ['label' => 'Agent Two',            'email' => 'a2@test.com',   'role' => Agent::ROLE_AGENT,           'parent' => 'AL1'],
        'A3'  => ['label' => 'Agent Three (Direct)', 'email' => 'a3@test.com',   'role' => Agent::ROLE_AGENT,           'parent' => 'BP1'],
        'A4'  => ['label' => 'Agent Four',           'email' => 'a4@test.com',   'role' => Agent::ROLE_AGENT,           'parent' => 'AL2'],
    ];

    private const SALES_PER_AGENT = 5;

    public function run(): void
    {
        $this->faker = Faker::create();

        $this->wipeTransactionalData();

        /** @var array<string, Agent> $created  key = short name, value = Agent model */
        $created = [];

        // Create in dependency order (parents first)
        foreach (self::AGENTS as $key => $spec) {
            $parentAgent = isset($spec['parent']) ? $created[$spec['parent']] : null;

            if (! $parentAgent && $spec['role'] === Agent::ROLE_BUSINESS_PARTNER) {
                $parentAgent = Agent::find(1);
            }
            $agent = $this->makeAgent($key, $spec, $parentAgent);
            $created[$key] = $agent;

            $this->command->info(sprintf(
                '  Created %-4s %-30s [%s]  referral: %s  parent: %s',
                $key,
                $spec['label'],
                $spec['role'],
                $agent->referralCode?->code ?? '—',
                $parentAgent ? $parentAgent->individual_name : 'none'
            ));
        }

        $months = 6;
        $this->command->info("Generating 10-40 sales per agent per month for the last $months months...");

        foreach ($created as $key => $agent) {
            if (! $agent->referralCode) {
                $this->command->warn("  {$key}: no referral code, skipping sales");
                continue;
            }

            for ($m = 0; $m < $months; $m++) {
                $monthDate = now()->subMonths($m);
                $startOfMonth = $monthDate->copy()->startOfMonth();
                $endOfMonth = $monthDate->copy()->endOfMonth();

                // If it's the current month, don't go beyond today
                if ($m === 0) {
                    $endOfMonth = now();
                }

                $salesCount = $this->faker->numberBetween(10, 40);

                // Also create some non-converting referrals (to simulate conversion rate < 100%)
                // Let's say conversion rate is 20-50%
                $referralCount = (int) ($salesCount / $this->faker->randomFloat(2, 0.2, 0.5));

                for ($r = 0; $r < $referralCount; $r++) {
                    $refDate = $this->faker->dateTimeBetween($startOfMonth, $endOfMonth);
                    Referral::create([
                        'referrer_id' => $agent->id,
                        'referred_email' => $this->faker->unique()->safeEmail,
                        'referred_name' => $this->faker->name,
                        'status' => 'pending',
                        'created_at' => $refDate,
                        'updated_at' => $refDate,
                    ]);
                }

                for ($i = 1; $i <= $salesCount; $i++) {
                    try {
                        $request = new Request();
                        $request->server->set('REMOTE_ADDR', $this->faker->ipv4);
                        $request->headers->set('User-Agent', $this->faker->userAgent);

                        $saleDate = $this->faker->dateTimeBetween($startOfMonth, $endOfMonth);

                        app(TrackingService::class)->trackSale([
                            'referral_code'  => $agent->referralCode->code,
                            'customer_name'  => $this->faker->name,
                            'customer_email' => 'buyer' . $this->faker->numberBetween(1000, 9999) . '@test.com',
                            'sale_amount'    => $this->faker->randomFloat(2, 200, 500),
                            'sale_date'      => $saleDate->format('Y-m-d'),
                            'product_name'   => 'Package ' . $this->faker->randomElement(['Basic', 'Premium', 'Standard']),
                            'invoice_number' => 'INV-' . strtoupper($key) . '-' . $monthDate->format('Ym') . '-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                        ], $request);
                    } catch (\Exception $e) {
                        // Skip errors (e.g. duplicate emails or validation issues)
                    }
                }
            }

            $this->command->info("  {$key}: Sales and referrals created for last $months months");
        }

        $this->command->newLine();
        $this->command->info('Done. Hierarchy summary:');
        $this->command->table(
            ['Key', 'Name', 'Role', 'Parent', 'Email', 'Password'],
            collect(self::AGENTS)->map(fn ($s, $k) => [
                $k,
                $s['label'],
                $s['role'],
                $s['parent'] ?? '—',
                $s['email'],
                'passw123',
            ])->values()->toArray()
        );

        $this->runCommissionScenarios($created);
    }

    /**
     * Wipe transactional rows before reseeding so the dataset is deterministic.
     * Truncates movement data and agent-scoped tables, then deletes all agents
     * except the canonical default BP at id=1. Users, system_settings, roles,
     * permissions, and activity logs are preserved.
     */
    private function wipeTransactionalData(): void
    {
        $truncateTables = [
            'payout_items',
            'payouts',
            'commissions',
            'sales',
            'referrals',
            'agent_visits',
            'fee_payments',
            'agent_notifications',
            'agent_commission_rates',
            'bank_accounts',
            'referral_codes',
            'agents_users',
        ];

        $this->command->info('Wiping transactional + agent-scoped data: ' . implode(', ', $truncateTables));
        $this->command->info('Deleting agents (preserving id=1)…');

        Schema::disableForeignKeyConstraints();
        foreach ($truncateTables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
            }
        }
        if (Schema::hasTable('agents')) {
            DB::table('agents')->where('id', '!=', 1)->delete();
        }
        Schema::enableForeignKeyConstraints();
    }

    /**
     * After the bulk historical sales, flip SystemSetting through 3 scenarios and
     * generate one extra sale per agent under each scenario. Sale description
     * carries the scenario label so the row is identifiable in the UI / DB.
     * Original setting values are captured up front and restored at the end.
     *
     * Scenarios:
     *   1) agent fixed RM3, leader fixed RM2, BP fixed RM1
     *   2) agent fixed RM3, leader fixed RM2, BP percentage 1.00%
     *   3) agent percentage 10%, leader fixed RM2, BP percentage 1.00%
     *
     * @param  array<string, Agent>  $agents
     */
    private function runCommissionScenarios(array $agents): void
    {
        $settings = SystemSetting::first();
        if (! $settings) {
            $this->command->warn('No SystemSetting row found — skipping commission scenarios.');
            return;
        }

        $touched = [
            'agent_own_sales',
            'agent_leader_override_agent',
            'business_partner_override_agent',
        ];

        // Snapshot current values so we can flip back at the end.
        $baseline = [];
        foreach ($touched as $key) {
            $baseline["{$key}_percentage"]   = $settings->{"{$key}_percentage"};
            $baseline["{$key}_fixed_amount"] = $settings->{"{$key}_fixed_amount"};
            $baseline["{$key}_calc_type"]    = $settings->{"{$key}_calc_type"};
        }

        $scenarios = [
            [
                'label'  => 'Scenario 1: agent fixed RM3, leader fixed RM2, BP fixed RM1',
                'config' => [
                    'agent_own_sales'                 => ['calc' => 'fixed',      'pct' => 0,  'fixed' => 3.00],
                    'agent_leader_override_agent'     => ['calc' => 'fixed',      'pct' => 0,  'fixed' => 2.00],
                    'business_partner_override_agent' => ['calc' => 'fixed',      'pct' => 0,  'fixed' => 1.00],
                ],
            ],
            [
                'label'  => 'Scenario 2: agent fixed RM3, leader fixed RM2, BP percentage 1.00%',
                'config' => [
                    'agent_own_sales'                 => ['calc' => 'fixed',      'pct' => 0,    'fixed' => 3.00],
                    'agent_leader_override_agent'     => ['calc' => 'fixed',      'pct' => 0,    'fixed' => 2.00],
                    'business_partner_override_agent' => ['calc' => 'percentage', 'pct' => 1.00, 'fixed' => 0],
                ],
            ],
            [
                'label'  => 'Scenario 3: agent percentage 10%, leader fixed RM2, BP percentage 1.00%',
                'config' => [
                    'agent_own_sales'                 => ['calc' => 'percentage', 'pct' => 10.00, 'fixed' => 0],
                    'agent_leader_override_agent'     => ['calc' => 'fixed',      'pct' => 0,     'fixed' => 2.00],
                    'business_partner_override_agent' => ['calc' => 'percentage', 'pct' => 1.00,  'fixed' => 0],
                ],
            ],
        ];

        foreach ($scenarios as $idx => $scenario) {
            $num = $idx + 1;
            $this->command->newLine();
            $this->command->info("=== {$scenario['label']} ===");

            $this->applyScenario($settings, $scenario['config']);
            Artisan::call('config:clear');
            \Illuminate\Support\Facades\Cache::forget(\App\Services\CommissionConfig::CACHE_KEY);

            $scenarioAgentKeys = ['A1', 'A2', 'A3', 'A4'];
            foreach ($scenarioAgentKeys as $key) {
                $agent = $agents[$key] ?? null;
                if (! $agent || ! $agent->referralCode) {
                    continue;
                }

                try {
                    $request = new Request();
                    $request->server->set('REMOTE_ADDR', $this->faker->ipv4);
                    $request->headers->set('User-Agent', $this->faker->userAgent);

                    app(TrackingService::class)->trackSale([
                        'referral_code'  => $agent->referralCode->code,
                        'customer_name'  => $this->faker->name,
                        'customer_email' => 'scenario' . $num . '-' . strtolower($key) . '@test.com',
                        'sale_amount'    => $this->faker->randomElement([100, 200, 300]),
                        'sale_date'      => now()->toDateString(),
                        'product_name'   => $scenario['label'],
                        'invoice_number' => 'SCN' . $num . '-' . strtoupper($key) . '-' . now()->format('YmdHis'),
                    ], $request);
                } catch (\Exception $e) {
                    $this->command->warn("  {$key}: scenario {$num} sale failed — " . $e->getMessage());
                }
            }

            $this->command->info("  Scenario {$num}: one sale generated for " . implode(', ', $scenarioAgentKeys) . '.');
        }

        // Flip back to baseline values.
        $this->command->newLine();
        $this->command->info('Restoring baseline commission settings…');
        $settings->fill($baseline)->save();
        Artisan::call('config:clear');
        \Illuminate\Support\Facades\Cache::forget(\App\Services\CommissionConfig::CACHE_KEY);
        $this->command->info('Baseline restored.');
    }

    /**
     * Apply a scenario config to the SystemSetting row (does not save baseline).
     *
     * @param  array<string, array{calc: string, pct: float, fixed: float}>  $config
     */
    private function applyScenario(SystemSetting $settings, array $config): void
    {
        foreach ($config as $key => $row) {
            $settings->{"{$key}_calc_type"}    = $row['calc'];
            $settings->{"{$key}_percentage"}   = $row['pct'];
            $settings->{"{$key}_fixed_amount"} = $row['fixed'];
        }
        $settings->save();
    }

    private function makeAgent(string $key, array $spec, ?Agent $parent): Agent
    {
        $user = User::firstOrCreate(
            ['email' => $spec['email']],
            [
                'name'              => $spec['label'],
                'password'          => Hash::make('passw123'),
                'email_verified_at' => now(),
            ]
        );

        $agent = Agent::create([
            'individual_name'  => $spec['label'],
            'individual_phone' => '+60' . $this->faker->numerify('1########'),
            'individual_address' => $this->faker->address,
            'profile_type'     => 'individual',
            'status'           => 'active',
            'agent_role'       => $spec['role'],
            'parent_agent_id'  => $parent?->id,
            'registered_at'    => now()->toDateString(),
            'expires_at'       => now()->addYear()->toDateString(),
            'renewal_due_at'   => now()->addYear()->subDays(30)->toDateString(),
            'fee_payment_status' => Agent::FEE_STATUS_PAID,
        ]);

        $user->agents()->attach($agent->id);

        // Assign Spatie role based on agent_role
        if ($agent->agent_role === Agent::ROLE_AGENT_LEADER) {
            $user->assignRole('agent_leader');
        } elseif ($agent->agent_role === Agent::ROLE_BUSINESS_PARTNER) {
            $user->assignRole('business_partner');
        } else {
            $user->assignRole('agent');
        }
        $agent->createReferralCode();

        BankAccount::create([
            'agent_id'       => $agent->id,
            'account_name'   => $spec['label'],
            'account_number' => $this->faker->numerify('##########'),
            'bank_name'      => 'Maybank',
            'iban'           => 'MY' . strtoupper(Str::random(18)),
            'swift_code'     => strtoupper(Str::random(8)),
        ]);

        return $agent;
    }
}
