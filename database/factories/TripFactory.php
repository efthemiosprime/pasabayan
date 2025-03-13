<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Trip>
 */
class TripFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $travelDate = $this->faker->dateTimeBetween('+1 day', '+2 weeks');
        $returnDate = $this->faker->dateTimeBetween($travelDate, '+4 weeks');

        return [
            'traveler_id' => User::factory()->create(['role' => 'traveler'])->id,
            'origin' => $this->faker->city(),
            'destination' => $this->faker->city(),
            'travel_date' => $travelDate,
            'return_date' => $returnDate,
            'available_capacity' => $this->faker->randomFloat(1, 0.5, 20),
            'transport_mode' => $this->faker->randomElement(['Car', 'Bus', 'Train', 'Airplane', 'Ship']),
            'notes' => $this->faker->paragraph(),
            'status' => $this->faker->randomElement(['active', 'completed', 'cancelled']),
        ];
    }

    /**
     * Indicate that the trip is active.
     */
    public function active(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'active',
            ];
        });
    }

    /**
     * Indicate that the trip is completed.
     */
    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'completed',
            ];
        });
    }

    /**
     * Indicate that the trip is cancelled.
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
