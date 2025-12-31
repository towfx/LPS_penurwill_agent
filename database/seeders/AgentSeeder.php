<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\BankAccount;
use App\Models\ReferralCode;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AgentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Individual Agents
        $individualAgents = [
            [
                'individual_name' => 'Sarah Johnson',
                'individual_phone' => '+1-555-0101',
                'individual_address' => '123 Oak Street, New York, NY 10001',
                'profile_type' => 'individual',
                'status' => 'active',
                'profile_image' => null,
                'user_email' => 'agent@mail.com',
            ],
            [
                'individual_name' => 'Michael Chen',
                'individual_phone' => '+1-555-0102',
                'individual_address' => '456 Pine Avenue, Los Angeles, CA 90210',
                'profile_type' => 'individual',
                'status' => 'active',
                'profile_image' => null,
                'user_email' => 'agent2@mail.com',
            ],
            [
                'individual_name' => 'Emily Rodriguez',
                'individual_phone' => '+1-555-0103',
                'individual_address' => '789 Maple Drive, Chicago, IL 60601',
                'profile_type' => 'individual',
                'status' => 'active',
                'profile_image' => null,
                'user_email' => 'agent3@mail.com',
            ],
            [
                'individual_name' => 'David Thompson',
                'individual_phone' => '+1-555-0104',
                'individual_address' => '321 Elm Court, Miami, FL 33101',
                'profile_type' => 'individual',
                'status' => 'active',
                'profile_image' => null,
                'user_email' => 'agent4@mail.com',
            ],
            [
                'individual_name' => 'Lisa Wang',
                'individual_phone' => '+1-555-0105',
                'individual_address' => '654 Birch Lane, Seattle, WA 98101',
                'profile_type' => 'individual',
                'status' => 'active',
                'profile_image' => null,
                'user_email' => 'agent5@mail.com',
            ],
        ];

        // Company Agents
        $companyAgents = [
            [
                'company_representative_name' => 'Robert Smith',
                'company_name' => 'TechSales Pro Solutions',
                'company_registration_number' => 'TSP-2024-001',
                'company_address' => '1000 Innovation Plaza, San Francisco, CA 94105',
                'company_phone' => '+1-555-0201',
                'profile_type' => 'company',
                'status' => 'active',
                'profile_image' => null,
                'user_email' => 'agent6@mail.com',
            ],
            [
                'company_representative_name' => 'Jennifer Davis',
                'company_name' => 'Global Marketing Partners',
                'company_registration_number' => 'GMP-2024-002',
                'company_address' => '2500 Business Center, Austin, TX 78701',
                'company_phone' => '+1-555-0202',
                'profile_type' => 'company',
                'status' => 'active',
                'profile_image' => null,
                'user_email' => 'agent7@mail.com',
            ],
            [
                'company_representative_name' => 'Christopher Lee',
                'company_name' => 'Digital Commerce Experts',
                'company_registration_number' => 'DCE-2024-003',
                'company_address' => '500 Tech Hub, Denver, CO 80202',
                'company_phone' => '+1-555-0203',
                'profile_type' => 'company',
                'status' => 'active',
                'profile_image' => null,
                'user_email' => 'agent8@mail.com',
            ],
            [
                'company_representative_name' => 'Amanda Wilson',
                'company_name' => 'Strategic Sales Alliance',
                'company_registration_number' => 'SSA-2024-004',
                'company_address' => '750 Corporate Tower, Boston, MA 02108',
                'company_phone' => '+1-555-0204',
                'profile_type' => 'company',
                'status' => 'active',
                'profile_image' => null,
                'user_email' => 'agent9@mail.com',
            ],
            [
                'company_representative_name' => 'Kevin Brown',
                'company_name' => 'Premium Solutions Group',
                'company_registration_number' => 'PSG-2024-005',
                'company_address' => '1200 Enterprise Way, Phoenix, AZ 85001',
                'company_phone' => '+1-555-0205',
                'profile_type' => 'company',
                'status' => 'active',
                'profile_image' => null,
                'user_email' => 'agent10@mail.com',
            ],
        ];

        // Bank names for random assignment
        $bankNames = [
            'Chase Bank',
            'Bank of America',
            'Wells Fargo',
            'Citibank',
            'US Bank',
            'PNC Bank',
            'Capital One',
            'TD Bank',
            'Goldman Sachs',
            'Morgan Stanley',
        ];

        // Create individual agents
        foreach ($individualAgents as $agentData) {
            $userEmail = $agentData['user_email'];
            unset($agentData['user_email']);

            // Create or find user
            $user = User::firstOrCreate(
                ['email' => $userEmail],
                [
                    'name' => $agentData['individual_name'],
                    'password' => Hash::make('passw123'),
                    'email_verified_at' => now(),
                ]
            );

            // Create agent
            $agent = Agent::create($agentData);

            // Link user to agent
            $user->agents()->attach($agent->id);

            // Create referral code for this agent using helper method
            $agent->createReferralCode();

            // Create bank account for this agent
            BankAccount::create([
                'agent_id' => $agent->id,
                'account_name' => $agent->individual_name,
                'account_number' => rand(100000000, 999999999),
                'bank_name' => $bankNames[array_rand($bankNames)],
                'iban' => 'US'.strtoupper(Str::random(18)),
                'swift_code' => strtoupper(Str::random(8)),
            ]);
        }

        // Create company agents
        foreach ($companyAgents as $agentData) {
            $userEmail = $agentData['user_email'];
            unset($agentData['user_email']);

            // Create or find user
            $user = User::firstOrCreate(
                ['email' => $userEmail],
                [
                    'name' => $agentData['company_representative_name'],
                    'password' => Hash::make('passw123'),
                    'email_verified_at' => now(),
                ]
            );

            // Create agent
            $agent = Agent::create($agentData);

            // Link user to agent
            $user->agents()->attach($agent->id);

            // Create referral code for this agent using helper method
            $agent->createReferralCode();

            // Create bank account for this agent
            BankAccount::create([
                'agent_id' => $agent->id,
                'account_name' => $agent->company_name,
                'account_number' => rand(100000000, 999999999),
                'bank_name' => $bankNames[array_rand($bankNames)],
                'iban' => 'US'.strtoupper(Str::random(18)),
                'swift_code' => strtoupper(Str::random(8)),
            ]);
        }

        $this->command->info('10 agents created successfully (5 individual, 5 company)');
        $this->command->info('User accounts created with default password: passw123');
        $this->command->info('Referral codes created for each agent');
        $this->command->info('Bank accounts created for each agent');
    }
}
