<?php

namespace Database\Factories;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DeliveryRequest>
 */
class DeliveryRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $trip = Trip::factory()->create();
        $deliveryDate = $this->faker->dateTimeBetween($trip->travel_date, $trip->return_date ?? '+1 month');

        return [
            'sender_id' => User::factory()->create(['role' => 'sender'])->id,
            'trip_id' => $trip->id,
            'pickup_location' => $this->faker->address(),
            'dropoff_location' => $this->faker->address(),
            'package_size' => $this->faker->randomElement(['small', 'medium', 'large', 'extra_large']),
            'package_weight' => $this->faker->randomFloat(2, 0.1, 50),
            'package_description' => $this->faker->sentence(),
            'urgency' => $this->faker->randomElement(['low', 'medium', 'high']),
            'delivery_date' => $deliveryDate,
            'status' => $this->faker->randomElement(['pending', 'accepted', 'in_transit', 'delivered', 'cancelled']),
            'special_instructions' => $this->faker->optional()->paragraph(),
            'estimated_cost' => $this->faker->randomFloat(2, 10, 1000),
        ];
    }

    /**
     * Indicate that the delivery request is pending.
     */
    public function pending(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending',
            ];
        });
    }

    /**
     * Indicate that the delivery request is accepted.
     */
    public function accepted(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'accepted',
            ];
        });
    }

    /**
     * Indicate that the delivery request is in transit.
     */
    public function inTransit(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'in_transit',
            ];
        });
    }

    /**
     * Indicate that the delivery request is delivered.
     */
    public function delivered(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'delivered',
            ];
        });
    }

    /**
     * Indicate that the delivery request is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'cancelled',
            ];
        });
    }
}
