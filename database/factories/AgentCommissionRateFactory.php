<?php

namespace Database\Factories;

use App\Models\AgentCommissionRate;
use App\Models\Agent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AgentCommissionRate>
 */
class AgentCommissionRateFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AgentCommissionRate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'agent_id' => Agent::factory(),
            'kind' => AgentCommissionRate::KIND_OWN_SALES,
            'custom_percentage' => $this->faker->optional()->randomFloat(2, 5, 25),
            'custom_fixed_amount' => 0,
            'commission_calc_type' => AgentCommissionRate::CALC_PERCENTAGE,
            'effective_from' => $this->faker->date(),
            'notes' => $this->faker->optional()->sentence(),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /**
     * Indicate that the commission rate is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Set a specific commission rate.
     */
    public function withRate(float $rate): static
    {
        return $this->state(fn (array $attributes) => [
            'custom_percentage' => $rate,
        ]);
    }
}
