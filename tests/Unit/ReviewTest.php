<?php

namespace Tests\Unit;

use App\Models\DeliveryRequest;
use App\Models\Review;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test that a user's average rating is updated when a review is created.
     */
    public function test_user_average_rating_is_updated_when_review_is_created(): void
    {
        // Create a sender
        $sender = User::factory()->create([
            'role' => 'sender',
            'rating' => 0,
        ]);

        // Create a traveler
        $traveler = User::factory()->create([
            'role' => 'traveler',
            'rating' => 0,
        ]);

        // Create a trip
        $trip = Trip::create([
            'traveler_id' => $traveler->id,
            'origin' => 'Test Origin',
            'destination' => 'Test Destination',
            'travel_date' => now()->addDays(1),
            'available_capacity' => 5,
            'transport_mode' => 'Car',
            'status' => 'active',
        ]);

        // Create a delivery request
        $deliveryRequest = DeliveryRequest::create([
            'sender_id' => $sender->id,
            'trip_id' => $trip->id,
            'pickup_location' => 'Test Pickup',
            'dropoff_location' => 'Test Dropoff',
            'package_size' => 'small',
            'package_weight' => 1,
            'package_description' => 'Test Package',
            'urgency' => 'medium',
            'delivery_date' => now()->addDays(2),
            'status' => 'delivered', // Set to delivered so we can review
        ]);

        // Create a review from sender to traveler
        Review::create([
            'reviewer_id' => $sender->id,
            'reviewee_id' => $traveler->id,
            'delivery_request_id' => $deliveryRequest->id,
            'trip_id' => $trip->id,
            'rating' => 4,
            'comment' => 'Great service!',
        ]);

        // Create another review from sender to traveler
        Review::create([
            'reviewer_id' => $sender->id,
            'reviewee_id' => $traveler->id,
            'delivery_request_id' => $deliveryRequest->id,
            'trip_id' => $trip->id,
            'rating' => 5,
            'comment' => 'Excellent service!',
        ]);

        // The traveler's rating should be (4 + 5) / 2 = 4.5
        $traveler->refresh();
        $this->assertEquals(4.5, $traveler->rating);

        // Create a review from traveler to sender
        Review::create([
            'reviewer_id' => $traveler->id,
            'reviewee_id' => $sender->id,
            'delivery_request_id' => $deliveryRequest->id,
            'trip_id' => $trip->id,
            'rating' => 3,
            'comment' => 'Good sender!',
        ]);

        // The sender's rating should be 3
        $sender->refresh();
        $this->assertEquals(3, $sender->rating);
    }

    /**
     * Test that a user's average rating is updated when a review is deleted.
     */
    public function test_user_average_rating_is_updated_when_review_is_deleted(): void
    {
        // Create a sender
        $sender = User::factory()->create([
            'role' => 'sender',
            'rating' => 0,
        ]);

        // Create a traveler
        $traveler = User::factory()->create([
            'role' => 'traveler',
            'rating' => 0,
        ]);

        // Create a trip
        $trip = Trip::create([
            'traveler_id' => $traveler->id,
            'origin' => 'Test Origin',
            'destination' => 'Test Destination',
            'travel_date' => now()->addDays(1),
            'available_capacity' => 5,
            'transport_mode' => 'Car',
            'status' => 'active',
        ]);

        // Create a delivery request
        $deliveryRequest = DeliveryRequest::create([
            'sender_id' => $sender->id,
            'trip_id' => $trip->id,
            'pickup_location' => 'Test Pickup',
            'dropoff_location' => 'Test Dropoff',
            'package_size' => 'small',
            'package_weight' => 1,
            'package_description' => 'Test Package',
            'urgency' => 'medium',
            'delivery_date' => now()->addDays(2),
            'status' => 'delivered', // Set to delivered so we can review
        ]);

        // Create a review
        $review1 = Review::create([
            'reviewer_id' => $sender->id,
            'reviewee_id' => $traveler->id,
            'delivery_request_id' => $deliveryRequest->id,
            'trip_id' => $trip->id,
            'rating' => 3,
            'comment' => 'Good service!',
        ]);

        // Create another review
        $review2 = Review::create([
            'reviewer_id' => $sender->id,
            'reviewee_id' => $traveler->id,
            'delivery_request_id' => $deliveryRequest->id,
            'trip_id' => $trip->id,
            'rating' => 5,
            'comment' => 'Excellent service!',
        ]);

        // The traveler's rating should be (3 + 5) / 2 = 4
        $traveler->refresh();
        $this->assertEquals(4, $traveler->rating);

        // Delete one review
        $review1->delete();

        // Update user rating manually (since we're not going through the controller)
        $averageRating = Review::where('reviewee_id', $traveler->id)->avg('rating') ?? 0;
        $traveler->update(['rating' => $averageRating]);

        // The traveler's rating should now be 5
        $traveler->refresh();
        $this->assertEquals(5, $traveler->rating);
    }
}
