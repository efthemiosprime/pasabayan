<?php

namespace Database\Factories;

use App\Models\DeliveryRequest;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Review>
 */
class ReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reviewer_id' => User::factory(),
            'reviewee_id' => User::factory(),
            'delivery_request_id' => DeliveryRequest::factory(),
            'trip_id' => Trip::factory(),
            'rating' => $this->faker->numberBetween(1, 5),
            'comment' => $this->faker->paragraph(),
        ];
    }

    /**
     * Indicate that the review is from a sender to a traveler.
     */
    public function fromSenderToTraveler(): static
    {
        return $this->state(function (array $attributes) {
            $sender = User::factory()->create(['role' => 'sender']);
            $traveler = User::factory()->create(['role' => 'traveler']);
            $trip = Trip::factory()->create(['traveler_id' => $traveler->id]);
            $deliveryRequest = DeliveryRequest::factory()->create([
                'sender_id' => $sender->id,
                'trip_id' => $trip->id,
                'status' => 'delivered',
            ]);

            return [
                'reviewer_id' => $sender->id,
                'reviewee_id' => $traveler->id,
                'delivery_request_id' => $deliveryRequest->id,
                'trip_id' => $trip->id,
            ];
        });
    }

    /**
     * Indicate that the review is from a traveler to a sender.
     */
    public function fromTravelerToSender(): static
    {
        return $this->state(function (array $attributes) {
            $sender = User::factory()->create(['role' => 'sender']);
            $traveler = User::factory()->create(['role' => 'traveler']);
            $trip = Trip::factory()->create(['traveler_id' => $traveler->id]);
            $deliveryRequest = DeliveryRequest::factory()->create([
                'sender_id' => $sender->id,
                'trip_id' => $trip->id,
                'status' => 'delivered',
            ]);

            return [
                'reviewer_id' => $traveler->id,
                'reviewee_id' => $sender->id,
                'delivery_request_id' => $deliveryRequest->id,
                'trip_id' => $trip->id,
            ];
        });
    }
}
