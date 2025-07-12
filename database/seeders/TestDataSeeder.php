<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\Sale;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // Get all agents with their referral codes
        $agents = Agent::with('referralCode')->get();

        if ($agents->isEmpty()) {
            $this->command->warn('No agents found. Please run AgentSeeder first.');
            return;
        }

        $this->command->info('Generating sales data for ' . $agents->count() . ' agents...');

        foreach ($agents as $agent) {
            if (!$agent->referralCode) {
                $this->command->warn("Agent {$agent->individual_name} has no referral code, skipping...");
                continue;
            }

            $this->command->info("Generating 30 sales for agent: {$agent->individual_name} (Code: {$agent->referralCode->code})");

            // Generate 30 sales for each agent
            for ($i = 1; $i <= 30; $i++) {
                try {
                    $saleData = [
                        'ip_address' => $faker->ipv4,
                        'user_agent' => $faker->userAgent,
                        'buyer_email' => 'customer' . $faker->numberBetween(1000, 9999) . '@' . $faker->freeEmailDomain,
                        'amount' => $faker->randomFloat(2, 200, 300),
                        'sale_date' => $faker->dateTimeBetween('-6 months', 'now'),
                        'description' => 'Package ' . $faker->randomElement(['Basic', 'Premium', 'Standard', 'Deluxe', 'Professional', 'Enterprise', 'Starter', 'Advanced', 'Ultimate', 'Custom']),
                        'invoice_number' => 'INV-' . $faker->numberBetween(10000, 99999),
                        'is_recurring' => $faker->boolean(20), // 20% chance of being recurring
                    ];

                    // Use the trackSale method to create the sale
                    Sale::trackSale($agent->referralCode->code, $saleData);

                } catch (\Exception $e) {
                    $this->command->error("Error creating sale {$i} for agent {$agent->individual_name}: " . $e->getMessage());
                }
            }
        }

        $this->command->info('Sales data generation completed successfully!');
    }
}
