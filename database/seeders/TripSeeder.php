<?php

namespace Database\Seeders;

use App\Models\Trip;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TripSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get traveler users
        $travelers = User::where('role', 'traveler')->get();
        
        // If no travelers exist, create the users first
        if ($travelers->isEmpty()) {
            $this->command->info('No travelers found. Creating users first...');
            $this->call(UsersTableSeeder::class);
            $travelers = User::where('role', 'traveler')->get();
        }
        
        // Some Philippine cities
        $cities = [
            'Manila', 'Quezon City', 'Davao City', 'Cebu City', 'Baguio City',
            'Iloilo City', 'Zamboanga City', 'Bacolod City', 'Cagayan de Oro',
            'Tagaytay City', 'Boracay', 'Palawan', 'Siargao', 'Batangas', 'Laguna'
        ];
        
        // Transport modes
        $transportModes = ['Bus', 'Car', 'Train', 'Plane', 'Ferry', 'Motorcycle'];
        
        // Status options
        $statuses = ['active', 'completed', 'cancelled'];
        
        // Create 15 trips
        for ($i = 0; $i < 15; $i++) {
            // Select random origin and destination, ensuring they're different
            $originIndex = array_rand($cities);
            $destIndex = array_rand($cities);
            while ($destIndex === $originIndex) {
                $destIndex = array_rand($cities);
            }
            
            // Create travel dates (some in the past, some in the future)
            $daysOffset = rand(-10, 30); // -10 to +30 days from now
            $travelDate = Carbon::now()->addDays($daysOffset);
            $returnDate = $travelDate->copy()->addDays(rand(1, 7)); // 1-7 days after travel
            
            // Set status based on dates
            $status = 'active';
            if ($travelDate->isPast() && $returnDate->isPast()) {
                $status = rand(0, 1) ? 'completed' : 'cancelled';
            } elseif ($travelDate->isPast() && $returnDate->isFuture()) {
                $status = 'active'; // Trip in progress
            }
            
            // Get a random traveler
            $traveler = $travelers->random();
            
            // Create the trip
            Trip::create([
                'traveler_id' => $traveler->id,
                'origin' => $cities[$originIndex],
                'destination' => $cities[$destIndex],
                'travel_date' => $travelDate,
                'return_date' => $returnDate,
                'available_capacity' => rand(1, 30) / 2, // 0.5 to 15 kg, in 0.5 increments
                'transport_mode' => $transportModes[array_rand($transportModes)],
                'notes' => "Trip from {$cities[$originIndex]} to {$cities[$destIndex]}. " . 
                          "I can carry packages up to " . (rand(1, 30) / 2) . " kg. " .
                          "Please contact me for more details.",
                'status' => $status,
            ]);
        }
        
        $this->command->info('15 trips created successfully!');
    }
} 