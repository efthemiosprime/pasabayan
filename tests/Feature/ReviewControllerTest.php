<?php

namespace Tests\Feature;

use App\Models\DeliveryRequest;
use App\Models\Review;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReviewControllerTest extends TestCase
{
    use DatabaseTransactions;
    use WithFaker;

    /**
     * Test that a user can create a review.
     */
    public function test_user_can_create_review(): void
    {
        // Create a sender and a traveler
        $sender = User::factory()->create(['role' => 'sender']);
        $traveler = User::factory()->create(['role' => 'traveler']);

        // Create a trip for the traveler
        $trip = Trip::factory()->create([
            'traveler_id' => $traveler->id,
            'status' => 'active',
        ]);

        // Create a delivery request
        $deliveryRequest = DeliveryRequest::factory()->create([
            'sender_id' => $sender->id,
            'trip_id' => $trip->id,
            'status' => 'delivered', // Must be delivered to leave a review
        ]);

        // Authenticate as the sender
        Sanctum::actingAs($sender);

        // Send a request to create a review
        $response = $this->postJson('/api/reviews', [
            'reviewee_id' => $traveler->id,
            'delivery_request_id' => $deliveryRequest->id,
            'trip_id' => $trip->id,
            'rating' => 4,
            'comment' => 'Great traveler, highly recommended!',
        ]);

        // Assert the response status is 201 (created)
        $response->assertStatus(201);

        // Assert the review was created in the database
        $this->assertDatabaseHas('reviews', [
            'reviewer_id' => $sender->id,
            'reviewee_id' => $traveler->id,
            'delivery_request_id' => $deliveryRequest->id,
            'trip_id' => $trip->id,
            'rating' => 4,
            'comment' => 'Great traveler, highly recommended!',
        ]);

        // Assert the traveler's rating was updated
        $traveler->refresh();
        $this->assertEquals(4, $traveler->rating);
    }

    /**
     * Test that a user cannot create a review for a delivery that is not delivered.
     */
    public function test_user_cannot_create_review_for_undelivered_request(): void
    {
        // Create a sender and a traveler
        $sender = User::factory()->create(['role' => 'sender']);
        $traveler = User::factory()->create(['role' => 'traveler']);

        // Create a trip for the traveler
        $trip = Trip::factory()->create([
            'traveler_id' => $traveler->id,
            'status' => 'active',
        ]);

        // Create a delivery request that is not delivered
        $deliveryRequest = DeliveryRequest::factory()->create([
            'sender_id' => $sender->id,
            'trip_id' => $trip->id,
            'status' => 'in_transit', // Not delivered
        ]);

        // Authenticate as the sender
        Sanctum::actingAs($sender);

        // Send a request to create a review
        $response = $this->postJson('/api/reviews', [
            'reviewee_id' => $traveler->id,
            'delivery_request_id' => $deliveryRequest->id,
            'trip_id' => $trip->id,
            'rating' => 4,
            'comment' => 'Great traveler!',
        ]);

        // Assert the response status is 400 (bad request)
        $response->assertStatus(400);

        // Assert the review was not created
        $this->assertDatabaseMissing('reviews', [
            'reviewer_id' => $sender->id,
            'reviewee_id' => $traveler->id,
            'delivery_request_id' => $deliveryRequest->id,
        ]);
    }

    /**
     * Test that a user can update their own review.
     */
    public function test_user_can_update_own_review(): void
    {
        // Create a sender and a traveler
        $sender = User::factory()->create(['role' => 'sender']);
        $traveler = User::factory()->create(['role' => 'traveler']);

        // Create a trip for the traveler
        $trip = Trip::factory()->create([
            'traveler_id' => $traveler->id,
        ]);

        // Create a delivery request
        $deliveryRequest = DeliveryRequest::factory()->create([
            'sender_id' => $sender->id,
            'trip_id' => $trip->id,
            'status' => 'delivered',
        ]);

        // Create a review
        $review = Review::create([
            'reviewer_id' => $sender->id,
            'reviewee_id' => $traveler->id,
            'delivery_request_id' => $deliveryRequest->id,
            'trip_id' => $trip->id,
            'rating' => 3,
            'comment' => 'Good traveler.',
        ]);

        // Authenticate as the sender
        Sanctum::actingAs($sender);

        // Send a request to update the review
        $response = $this->putJson("/api/reviews/{$review->id}", [
            'rating' => 5,
            'comment' => 'Actually, this traveler was excellent!',
        ]);

        // Assert the response status is 200 (OK)
        $response->assertStatus(200);

        // Assert the review was updated
        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 5,
            'comment' => 'Actually, this traveler was excellent!',
        ]);

        // Assert the traveler's rating was updated
        $traveler->refresh();
        $this->assertEquals(5, $traveler->rating);
    }

    /**
     * Test that a user cannot update another user's review.
     */
    public function test_user_cannot_update_other_users_review(): void
    {
        // Create a sender, a traveler, and another user
        $sender = User::factory()->create(['role' => 'sender']);
        $traveler = User::factory()->create(['role' => 'traveler']);
        $otherUser = User::factory()->create(['role' => 'sender']);

        // Create a trip for the traveler
        $trip = Trip::factory()->create([
            'traveler_id' => $traveler->id,
        ]);

        // Create a delivery request
        $deliveryRequest = DeliveryRequest::factory()->create([
            'sender_id' => $sender->id,
            'trip_id' => $trip->id,
            'status' => 'delivered',
        ]);

        // Create a review by the sender
        $review = Review::create([
            'reviewer_id' => $sender->id,
            'reviewee_id' => $traveler->id,
            'delivery_request_id' => $deliveryRequest->id,
            'trip_id' => $trip->id,
            'rating' => 3,
            'comment' => 'Good traveler.',
        ]);

        // Authenticate as the other user
        Sanctum::actingAs($otherUser);

        // Send a request to update the review
        $response = $this->putJson("/api/reviews/{$review->id}", [
            'rating' => 1,
            'comment' => 'Trying to change someone else\'s review!',
        ]);

        // Assert the response status is 403 (forbidden)
        $response->assertStatus(403);

        // Assert the review was not updated
        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 3,
            'comment' => 'Good traveler.',
        ]);
    }

    /**
     * Test that a user can delete their own review.
     */
    public function test_user_can_delete_own_review(): void
    {
        // Create a sender and a traveler
        $sender = User::factory()->create(['role' => 'sender']);
        $traveler = User::factory()->create(['role' => 'traveler']);

        // Create a trip for the traveler
        $trip = Trip::factory()->create([
            'traveler_id' => $traveler->id,
        ]);

        // Create a delivery request
        $deliveryRequest = DeliveryRequest::factory()->create([
            'sender_id' => $sender->id,
            'trip_id' => $trip->id,
            'status' => 'delivered',
        ]);

        // Create a review
        $review = Review::create([
            'reviewer_id' => $sender->id,
            'reviewee_id' => $traveler->id,
            'delivery_request_id' => $deliveryRequest->id,
            'trip_id' => $trip->id,
            'rating' => 3,
            'comment' => 'Good traveler.',
        ]);

        // Authenticate as the sender
        Sanctum::actingAs($sender);

        // Send a request to delete the review
        $response = $this->deleteJson("/api/reviews/{$review->id}");

        // Assert the response status is 200 (OK)
        $response->assertStatus(200);

        // Assert the review was deleted
        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);
    }

    /**
     * Test that a user can view their given reviews.
     */
    public function test_user_can_view_given_reviews(): void
    {
        // Create a sender and two travelers
        $sender = User::factory()->create(['role' => 'sender']);
        $traveler1 = User::factory()->create(['role' => 'traveler']);
        $traveler2 = User::factory()->create(['role' => 'traveler']);

        // Create trips for the travelers
        $trip1 = Trip::factory()->create([
            'traveler_id' => $traveler1->id,
        ]);
        $trip2 = Trip::factory()->create([
            'traveler_id' => $traveler2->id,
        ]);

        // Create delivery requests
        $deliveryRequest1 = DeliveryRequest::factory()->create([
            'sender_id' => $sender->id,
            'trip_id' => $trip1->id,
            'status' => 'delivered',
        ]);
        $deliveryRequest2 = DeliveryRequest::factory()->create([
            'sender_id' => $sender->id,
            'trip_id' => $trip2->id,
            'status' => 'delivered',
        ]);

        // Create reviews by the sender
        $review1 = Review::create([
            'reviewer_id' => $sender->id,
            'reviewee_id' => $traveler1->id,
            'delivery_request_id' => $deliveryRequest1->id,
            'trip_id' => $trip1->id,
            'rating' => 4,
            'comment' => 'Great traveler 1.',
        ]);
        $review2 = Review::create([
            'reviewer_id' => $sender->id,
            'reviewee_id' => $traveler2->id,
            'delivery_request_id' => $deliveryRequest2->id,
            'trip_id' => $trip2->id,
            'rating' => 5,
            'comment' => 'Great traveler 2.',
        ]);

        // Authenticate as the sender
        Sanctum::actingAs($sender);

        // Send a request to view the sender's given reviews
        $response = $this->getJson('/api/my-given-reviews');

        // Assert the response status is 200 (OK)
        $response->assertStatus(200);

        // Assert the response contains both reviews
        $response->assertJsonCount(2, 'data.data');
        $response->assertJsonPath('data.data.0.rating', 4); // First review
        $response->assertJsonPath('data.data.1.rating', 5); // Second review
    }

    /**
     * Test that a user can view their received reviews.
     */
    public function test_user_can_view_received_reviews(): void
    {
        // Create a traveler and two senders
        $traveler = User::factory()->create(['role' => 'traveler']);
        $sender1 = User::factory()->create(['role' => 'sender']);
        $sender2 = User::factory()->create(['role' => 'sender']);

        // Create a trip for the traveler
        $trip = Trip::factory()->create([
            'traveler_id' => $traveler->id,
        ]);

        // Create delivery requests
        $deliveryRequest1 = DeliveryRequest::factory()->create([
            'sender_id' => $sender1->id,
            'trip_id' => $trip->id,
            'status' => 'delivered',
        ]);
        $deliveryRequest2 = DeliveryRequest::factory()->create([
            'sender_id' => $sender2->id,
            'trip_id' => $trip->id,
            'status' => 'delivered',
        ]);

        // Create reviews for the traveler
        $review1 = Review::create([
            'reviewer_id' => $sender1->id,
            'reviewee_id' => $traveler->id,
            'delivery_request_id' => $deliveryRequest1->id,
            'trip_id' => $trip->id,
            'rating' => 4,
            'comment' => 'Good traveler from sender 1.',
        ]);
        $review2 = Review::create([
            'reviewer_id' => $sender2->id,
            'reviewee_id' => $traveler->id,
            'delivery_request_id' => $deliveryRequest2->id,
            'trip_id' => $trip->id,
            'rating' => 5,
            'comment' => 'Excellent traveler from sender 2.',
        ]);

        // Authenticate as the traveler
        Sanctum::actingAs($traveler);

        // Send a request to view the traveler's received reviews
        $response = $this->getJson('/api/my-received-reviews');

        // Assert the response status is 200 (OK)
        $response->assertStatus(200);

        // Assert the response contains both reviews
        $response->assertJsonCount(2, 'data.data');
        $response->assertJsonPath('data.data.0.rating', 4); // First review
        $response->assertJsonPath('data.data.1.rating', 5); // Second review
    }
}
