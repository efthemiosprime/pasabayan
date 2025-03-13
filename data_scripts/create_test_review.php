<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Review;
use App\Models\DeliveryRequest;
use App\Models\Trip;
use Illuminate\Support\Facades\DB;

// Find the user with the given email
$user = User::where('email', 'bongbox@gmail.com')->first();

if (!$user) {
    echo "User with email bongbox@gmail.com not found. Creating user...\n";
    
    // Create a user if not exists
    $user = User::create([
        'name' => 'Bong Box',
        'email' => 'bongbox@gmail.com',
        'password' => bcrypt('password123'),
        'role' => 'sender', // or 'traveler'
    ]);
    
    echo "User created with ID: {$user->id}\n";
} else {
    echo "Found user with ID: {$user->id} and name: {$user->name}\n";
}

// Find or create another user to be the reviewer
$reviewer = User::where('email', '!=', 'bongbox@gmail.com')->first();

if (!$reviewer) {
    echo "No other user found to be reviewer. Creating one...\n";
    
    // Create a reviewer if no other user exists
    $reviewer = User::create([
        'name' => 'Test Reviewer',
        'email' => 'reviewer@example.com',
        'password' => bcrypt('password123'),
        'role' => 'traveler', // opposite of user role
    ]);
    
    echo "Reviewer created with ID: {$reviewer->id}\n";
} else {
    echo "Found reviewer with ID: {$reviewer->id} and name: {$reviewer->name}\n";
}

// Check if a delivery request exists
$deliveryRequest = DeliveryRequest::first();

if (!$deliveryRequest) {
    echo "No delivery request found. Creating one...\n";
    
    // Create a trip first if needed
    $trip = Trip::first();
    
    if (!$trip) {
        echo "No trip found. Creating one...\n";
        
        $trip = Trip::create([
            'traveler_id' => $reviewer->id,
            'origin' => 'Test Origin',
            'destination' => 'Test Destination',
            'travel_date' => now()->addDays(7),
            'return_date' => now()->addDays(14),
            'available_capacity' => 10,
            'notes' => 'Test trip created for review testing',
        ]);
        
        echo "Trip created with ID: {$trip->id}\n";
    }
    
    // Create a delivery request
    $deliveryRequest = DeliveryRequest::create([
        'sender_id' => $user->id,
        'trip_id' => $trip->id,
        'item_description' => 'Test Item',
        'pickup_location' => 'Test Pickup',
        'dropoff_location' => 'Test Dropoff',
        'package_size' => 'medium',
        'status' => 'completed', // Set as completed so we can review
    ]);
    
    echo "Delivery request created with ID: {$deliveryRequest->id}\n";
} else {
    echo "Found delivery request with ID: {$deliveryRequest->id}\n";
}

// Create the review
$review = Review::create([
    'reviewer_id' => $reviewer->id,
    'reviewee_id' => $user->id,
    'delivery_request_id' => $deliveryRequest->id,
    'trip_id' => $deliveryRequest->trip_id,
    'rating' => 4.5,
    'comment' => 'Great experience working with this user! Very responsive and the package was as described. Would definitely recommend.',
    'created_at' => now(),
]);

echo "Review created successfully with ID: {$review->id}\n";
echo "Review details:\n";
echo "Rating: {$review->rating}\n";
echo "Comment: {$review->comment}\n";
echo "From: {$reviewer->name} (ID: {$reviewer->id})\n";
echo "To: {$user->name} (ID: {$user->id})\n";
echo "Done!\n"; 