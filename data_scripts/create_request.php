<?php

// This script creates a delivery request for a specific user

// Bootstrap the Laravel application
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Find the user with the given email
$user = \App\Models\User::where('email', 'bongbox@gmail.com')->first();

if (!$user) {
    echo "User not found with email bongbox@gmail.com\n";
    exit(1);
}

echo "Found user: {$user->name} (ID: {$user->id})\n";

// Find an available trip to associate with the request
$trip = \App\Models\Trip::where('status', 'active')->first();

// If no upcoming trip is available, create one
if (!$trip) {
    echo "No active trips found. Creating a new trip...\n";
    
    // Find a traveler user (different from our sender)
    $traveler = \App\Models\User::where('role', 'traveler')
                               ->where('id', '!=', $user->id)
                               ->first();
    
    if (!$traveler) {
        // If no other traveler is found, use any user as traveler
        $traveler = \App\Models\User::where('id', '!=', $user->id)->first();
        
        if (!$traveler) {
            echo "Could not find a traveler for the trip\n";
            exit(1);
        }
    }
    
    // Create a new trip
    $trip = new \App\Models\Trip();
    $trip->traveler_id = $traveler->id;
    $trip->origin = 'Manila';
    $trip->destination = 'Cebu';
    $trip->travel_date = now()->addDays(3)->format('Y-m-d H:i:s');
    $trip->return_date = now()->addDays(3)->addHours(2)->format('Y-m-d H:i:s');
    $trip->available_capacity = 10;
    $trip->transport_mode = 'air';
    $trip->notes = 'Sample trip created for testing';
    $trip->status = 'active';
    $trip->save();
    
    echo "Created new trip: Manila to Cebu (ID: {$trip->id})\n";
}

echo "Using trip: {$trip->origin} to {$trip->destination} (ID: {$trip->id})\n";

// Create a new delivery request
$request = new \App\Models\DeliveryRequest();
$request->trip_id = $trip->id;
$request->sender_id = $user->id;
$request->package_description = 'Sample Package - Books and Electronics';
$request->pickup_location = '123 Main St, Manila';
$request->dropoff_location = '456 Oak Ave, Cebu';
$request->package_size = 'medium'; // Enum: small, medium, large, extra_large
$request->package_weight = 2.5;
$request->urgency = 'medium'; // Enum: low, medium, high
$request->delivery_date = now()->addDays(4)->format('Y-m-d H:i:s');
$request->special_instructions = 'Please handle with care, fragile items inside';
$request->status = 'pending';
$request->estimated_cost = 500;
$request->save();

echo "Delivery request created successfully!\n";
echo "Request ID: {$request->id}\n";
echo "For Trip: {$trip->origin} to {$trip->destination}\n";
echo "Package: {$request->package_description}\n";

echo "\nCommand to create this request:\n";
echo "php artisan tinker --execute=\"
\$user = \\App\\Models\\User::where('email', 'bongbox@gmail.com')->first();
\$trip = \\App\\Models\\Trip::where('id', {$trip->id})->first();
\$request = new \\App\\Models\\DeliveryRequest();
\$request->trip_id = \$trip->id;
\$request->sender_id = \$user->id;
\$request->package_description = 'Sample Package - Books and Electronics';
\$request->pickup_location = '123 Main St, Manila';
\$request->dropoff_location = '456 Oak Ave, Cebu';
\$request->package_size = 'medium';
\$request->package_weight = 2.5;
\$request->urgency = 'medium';
\$request->delivery_date = now()->addDays(4)->format('Y-m-d H:i:s');
\$request->special_instructions = 'Please handle with care, fragile items inside';
\$request->status = 'pending';
\$request->estimated_cost = 500;
\$request->save();
echo 'Created request ID: '.\$request->id;
\"\n"; 