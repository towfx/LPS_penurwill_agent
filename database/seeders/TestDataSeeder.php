<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\BankAccount;
use App\Models\Sale;
use App\Models\User;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
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

        $this->command->newLine();
        $this->command->info('Generating ' . self::SALES_PER_AGENT . ' sales per agent...');

        foreach ($created as $key => $agent) {
            if (! $agent->referralCode) {
                $this->command->warn("  {$key}: no referral code, skipping sales");
                continue;
            }

            for ($i = 1; $i <= self::SALES_PER_AGENT; $i++) {
                try {
                    Sale::trackSale($agent->referralCode->code, [
                        'ip_address'     => $this->faker->ipv4,
                        'user_agent'     => $this->faker->userAgent,
                        'buyer_email'    => 'buyer' . $this->faker->numberBetween(1000, 9999) . '@test.com',
                        'amount'         => $this->faker->randomFloat(2, 200, 500),
                        'sale_date'      => $this->faker->dateTimeBetween('-3 months', 'now'),
                        'description'    => 'Package ' . $this->faker->randomElement(['Basic', 'Premium', 'Standard']),
                        'invoice_number' => 'INV-' . strtoupper($key) . '-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                        'is_recurring'   => false,
                    ]);
                } catch (\Exception $e) {
                    $this->command->error("  Sale {$i} for {$key} failed: " . $e->getMessage());
                }
            }

            $this->command->info("  {$key}: " . self::SALES_PER_AGENT . ' sales created');
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
