<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Agent>
 */
class AgentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Agent::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $profileType = $this->faker->randomElement(['individual', 'company']);

        $base = [
            'profile_type' => $profileType,
            'referral_code_id' => null,
            'status' => 'active',
            'profile_image' => null,
            'agent_role' => Agent::ROLE_AGENT,
            'parent_agent_id' => null,
            'is_default' => false,
            'fee_payment_status' => Agent::FEE_STATUS_PENDING,
            'registered_at' => now()->toDateString(),
            'expires_at' => now()->addYear()->toDateString(),
            'renewal_due_at' => now()->addYear()->subDays(30)->toDateString(),
        ];

        if ($profileType === 'individual') {
            return $base + [
                'individual_name' => $this->faker->name(),
                'individual_phone' => $this->faker->phoneNumber(),
                'individual_address' => $this->faker->address(),
                'company_representative_name' => null,
                'company_name' => null,
                'company_registration_number' => null,
                'company_address' => null,
                'company_phone' => null,
            ];
        }

        return $base + [
            'individual_name' => null,
            'individual_phone' => null,
            'individual_address' => null,
            'company_representative_name' => $this->faker->name(),
            'company_name' => $this->faker->company(),
            'company_registration_number' => $this->faker->numerify('REG-#####'),
            'company_address' => $this->faker->address(),
            'company_phone' => $this->faker->phoneNumber(),
        ];
    }

    /**
     * Indicate that the agent is an Agent Leader.
     */
    public function agentLeader(): static
    {
        return $this->state(fn (array $attributes) => [
            'agent_role' => Agent::ROLE_AGENT_LEADER,
        ]);
    }

    /**
     * Indicate that the agent is a Business Partner.
     */
    public function businessPartner(): static
    {
        return $this->state(fn (array $attributes) => [
            'agent_role' => Agent::ROLE_BUSINESS_PARTNER,
        ]);
    }

    /**
     * Place this agent under a given parent.
     */
    public function under(Agent $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_agent_id' => $parent->id,
        ]);
    }

    /**
     * Indicate that the agent is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'expires_at' => now()->subDay()->toDateString(),
            'fee_payment_status' => Agent::FEE_STATUS_OVERDUE,
        ]);
    }

    /**
     * Indicate that the agent is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    /**
     * Indicate that the agent is an individual.
     */
    public function individual(): static
    {
        return $this->state(fn (array $attributes) => [
            'profile_type' => 'individual',
            'individual_name' => $this->faker->name(),
            'individual_phone' => $this->faker->phoneNumber(),
            'individual_address' => $this->faker->address(),
            'company_representative_name' => null,
            'company_name' => null,
            'company_registration_number' => null,
            'company_address' => null,
            'company_phone' => null,
        ]);
    }

    /**
     * Indicate that the agent is a company.
     */
    public function company(): static
    {
        return $this->state(fn (array $attributes) => [
            'profile_type' => 'company',
            'individual_name' => null,
            'individual_phone' => null,
            'individual_address' => null,
            'company_representative_name' => $this->faker->name(),
            'company_name' => $this->faker->company(),
            'company_registration_number' => $this->faker->numerify('REG-#####'),
            'company_address' => $this->faker->address(),
            'company_phone' => $this->faker->phoneNumber(),
        ]);
    }
}
