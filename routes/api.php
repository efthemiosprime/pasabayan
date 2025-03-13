<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\DeliveryRequestController;
use App\Http\Controllers\API\MatchController;
use App\Http\Controllers\API\MessageController;
use App\Http\Controllers\API\PaymentController;
use App\Http\Controllers\API\ReviewController;
use App\Http\Controllers\API\TripController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->withoutMiddleware([\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class]);
Route::get('/public/trips', [TripController::class, 'index']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);

    // Trip routes
    Route::get('/trips', [TripController::class, 'index']);
    Route::post('/trips', [TripController::class, 'store']);
    Route::get('/trips/{id}', [TripController::class, 'show']);
    Route::put('/trips/{id}', [TripController::class, 'update']);
    Route::delete('/trips/{id}', [TripController::class, 'destroy']);
    Route::get('/my-trips', [TripController::class, 'myTrips']);
    Route::get('/available-trips', [TripController::class, 'availableTrips']);

    // Delivery Request routes
    Route::get('/requests', [DeliveryRequestController::class, 'index']);
    Route::post('/requests', [DeliveryRequestController::class, 'store']);
    Route::get('/requests/{id}', [DeliveryRequestController::class, 'show']);
    Route::put('/requests/{id}', [DeliveryRequestController::class, 'update']);
    Route::delete('/requests/{id}', [DeliveryRequestController::class, 'destroy']);
    Route::get('/my-requests', [DeliveryRequestController::class, 'myDeliveryRequests']);
    Route::post('/requests/{id}/accept', [DeliveryRequestController::class, 'acceptRequest']);
    Route::put('/requests/{id}/cancel', [DeliveryRequestController::class, 'cancelRequest']);
    Route::put('/requests/{id}/complete', [DeliveryRequestController::class, 'completeRequest']);

    // Matching routes
    Route::get('/matches/trip/{trip_id}', [MatchController::class, 'getMatchingRequests']);
    Route::get('/matches/request/{request_id}', [MatchController::class, 'getMatchingTrips']);
    Route::post('/matches/assign', [MatchController::class, 'assignRequestToTrip']);
    
    // Review routes
    Route::get('/reviews', [ReviewController::class, 'index']);
    Route::post('/reviews', [ReviewController::class, 'store']);
    Route::get('/reviews/{id}', [ReviewController::class, 'show']);
    Route::put('/reviews/{id}', [ReviewController::class, 'update']);
    Route::delete('/reviews/{id}', [ReviewController::class, 'destroy']);
    Route::get('/my-given-reviews', [ReviewController::class, 'myGivenReviews']);
    Route::get('/my-received-reviews', [ReviewController::class, 'myReceivedReviews']);
    Route::get('/users/{userId}/review-stats', [ReviewController::class, 'getUserReviewStats']);
    Route::get('/users/{userId}/reviews', [ReviewController::class, 'getUserReviews']);

    // Admin only routes
    Route::middleware('admin')->group(function () {
        Route::get('/matches', [MatchController::class, 'getAllMatches']);
    });
}); 