<?php

namespace Database\Factories;

use App\Models\PaymentCollection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentItem>
 */
class PaymentItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_collection_id' => PaymentCollection::factory(),
            'name' => fake()->words(2, true),
            'description' => fake()->optional()->sentence(),
            'price' => fake()->randomFloat(2, 10, 1000),
            'quantity' => fake()->numberBetween(1, 10),
            'type' => fake()->randomElement(['service', 'product', 'fee']),
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }

    public function service(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'service',
        ]);
    }

    public function product(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'product',
        ]);
    }

    public function fee(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'fee',
        ]);
    }
}
