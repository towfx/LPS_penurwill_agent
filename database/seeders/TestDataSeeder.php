<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\BankAccount;
use App\Models\Referral;
use App\Models\Sale;
use App\Models\User;
use App\Services\TrackingService;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
