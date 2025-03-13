<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\DeliveryRequest;
use App\Models\Review;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    /**
     * Display a listing of the reviews.
     */
    public function index(Request $request)
    {
        $query = Review::with(['reviewer', 'reviewee']);

        // Filter by reviewer
        if ($request->has('reviewer_id')) {
            $query->where('reviewer_id', $request->reviewer_id);
        }

        // Filter by reviewee
        if ($request->has('reviewee_id')) {
            $query->where('reviewee_id', $request->reviewee_id);
        }

        // Filter by trip
        if ($request->has('trip_id')) {
            $query->where('trip_id', $request->trip_id);
        }

        // Filter by delivery request
        if ($request->has('delivery_request_id')) {
            $query->where('delivery_request_id', $request->delivery_request_id);
        }

        // Filter by minimum rating
        if ($request->has('min_rating')) {
            $query->where('rating', '>=', $request->min_rating);
        }

        $reviews = $query->latest()->paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => $reviews
        ]);
    }

    /**
     * Store a newly created review in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reviewee_id' => 'required|exists:users,id',
            'delivery_request_id' => 'required|exists:delivery_requests,id',
            'trip_id' => 'required|exists:trips,id',
            'rating' => 'required|numeric|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if the delivery request and trip exist and are connected
        $deliveryRequest = DeliveryRequest::findOrFail($request->delivery_request_id);
        $trip = Trip::findOrFail($request->trip_id);

        if ($deliveryRequest->trip_id != $trip->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'The delivery request is not associated with the specified trip'
            ], 400);
        }

        // Check if the delivery request is delivered
        if ($deliveryRequest->status !== 'delivered') {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot review a delivery request that is not completed'
            ], 400);
        }

        // Check if the requesting user is part of this transaction
        $reviewerId = $request->user()->id;
        $isSender = $reviewerId === $deliveryRequest->sender_id;
        $isTraveler = $reviewerId === $trip->traveler_id;

        if (!$isSender && !$isTraveler) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to review this transaction'
            ], 403);
        }

        // Check if the reviewee is the other party in the transaction
        $revieweeId = $request->reviewee_id;
        $validReviewee = false;

        if ($isSender && $revieweeId === $trip->traveler_id) {
            $validReviewee = true; // Sender reviewing traveler
        } elseif ($isTraveler && $revieweeId === $deliveryRequest->sender_id) {
            $validReviewee = true; // Traveler reviewing sender
        }

        if (!$validReviewee) {
            return response()->json([
                'status' => 'error',
                'message' => 'The reviewee must be the other party in the transaction'
            ], 400);
        }

        // Check if a review already exists
        $existingReview = Review::where('reviewer_id', $reviewerId)
            ->where('reviewee_id', $revieweeId)
            ->where('delivery_request_id', $request->delivery_request_id)
            ->where('trip_id', $request->trip_id)
            ->first();

        if ($existingReview) {
            return response()->json([
                'status' => 'error',
                'message' => 'You have already reviewed this transaction'
            ], 400);
        }

        // Create the review
        $review = Review::create([
            'reviewer_id' => $reviewerId,
            'reviewee_id' => $revieweeId,
            'delivery_request_id' => $request->delivery_request_id,
            'trip_id' => $request->trip_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        // Update the user's average rating
        $this->updateUserRating($revieweeId);

        return response()->json([
            'status' => 'success',
            'message' => 'Review created successfully',
            'data' => $review
        ], 201);
    }

    /**
     * Display the specified review.
     */
    public function show(string $id)
    {
        $review = Review::with(['reviewer', 'reviewee', 'deliveryRequest', 'trip'])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $review
        ]);
    }

    /**
     * Update the specified review in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'rating' => 'nullable|numeric|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $review = Review::findOrFail($id);

        // Check if the user is the reviewer
        if ($request->user()->id !== $review->reviewer_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to update this review'
            ], 403);
        }

        // Update the review
        $review->update([
            'rating' => $request->rating ?? $review->rating,
            'comment' => $request->comment ?? $review->comment,
        ]);

        // Update the user's average rating
        $this->updateUserRating($review->reviewee_id);

        return response()->json([
            'status' => 'success',
            'message' => 'Review updated successfully',
            'data' => $review
        ]);
    }

    /**
     * Remove the specified review from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $review = Review::findOrFail($id);

        // Check if the user is the reviewer
        if ($request->user()->id !== $review->reviewer_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to delete this review'
            ], 403);
        }

        // Store the reviewee ID before deleting the review
        $revieweeId = $review->reviewee_id;

        $review->delete();

        // Update the user's average rating
        $this->updateUserRating($revieweeId);

        return response()->json([
            'status' => 'success',
            'message' => 'Review deleted successfully'
        ]);
    }

    /**
     * Get reviews given by the authenticated user.
     */
    public function myGivenReviews(Request $request)
    {
        $reviews = Review::with(['reviewee', 'deliveryRequest', 'trip'])
            ->where('reviewer_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => $reviews
        ]);
    }

    /**
     * Get reviews received by the authenticated user.
     */
    public function myReceivedReviews(Request $request)
    {
        $reviews = Review::with(['reviewer', 'deliveryRequest', 'trip'])
            ->where('reviewee_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => $reviews
        ]);
    }

    /**
     * Get review statistics for a user.
     */
    public function getUserReviewStats(Request $request, string $userId)
    {
        try {
            $user = User::findOrFail($userId);
            
            // Get all reviews for the user
            $reviews = Review::where('reviewee_id', $userId)->get();
            
            // Calculate stats
            $totalReviews = $reviews->count();
            $averageRating = $totalReviews > 0 ? $reviews->avg('rating') : 0;
            
            // Calculate rating distribution
            $ratingDistribution = [
                5 => 0,
                4 => 0,
                3 => 0,
                2 => 0,
                1 => 0
            ];
            
            foreach ($reviews as $review) {
                $rating = min(5, max(1, floor($review->rating)));
                $ratingDistribution[$rating]++;
            }
            
            return response()->json([
                'success' => true,
                'stats' => [
                    'totalReviews' => $totalReviews,
                    'averageRating' => $averageRating,
                    'ratingDistribution' => $ratingDistribution
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve review statistics'
            ], 500);
        }
    }

    /**
     * Get reviews for a specific user.
     */
    public function getUserReviews(Request $request, string $userId)
    {
        try {
            $user = User::findOrFail($userId);
            
            $reviews = Review::with('reviewer')
                ->where('reviewee_id', $userId)
                ->latest()
                ->paginate(10);
            
            return response()->json([
                'success' => true,
                'reviews' => $reviews,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'profile_photo' => $user->profile_photo,
                    'rating' => $user->rating,
                    'role' => $user->role
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user reviews'
            ], 500);
        }
    }

    /**
     * Update the user rating based on reviews.
     */
    private function updateUserRating(int $userId)
    {
        $user = User::findOrFail($userId);
        $averageRating = Review::where('reviewee_id', $userId)->avg('rating') ?? 0;
        $user->update(['rating' => $averageRating]);
    }
}
