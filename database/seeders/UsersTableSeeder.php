<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@pasabay.com',
            'password' => Hash::make('password'),
            'phone' => '1234567890',
            'address' => '123 Admin St',
            'city' => 'Admin City',
            'state' => 'Admin State',
            'country' => 'Philippines',
            'postal_code' => '12345',
            'is_verified' => true,
            'role' => 'admin',
            'bio' => 'I am the admin of Pasabay.',
        ]);

        // Create traveler user
        User::create([
            'name' => 'Traveler User',
            'email' => 'traveler@pasabay.com',
            'password' => Hash::make('password'),
            'phone' => '0987654321',
            'address' => '456 Traveler St',
            'city' => 'Traveler City',
            'state' => 'Traveler State',
            'country' => 'Philippines',
            'postal_code' => '54321',
            'is_verified' => true,
            'role' => 'traveler',
            'rating' => 4.5,
            'bio' => 'I am a traveler who loves to help others deliver packages.',
        ]);

        // Create sender user
        User::create([
            'name' => 'Sender User',
            'email' => 'sender@pasabay.com',
            'password' => Hash::make('password'),
            'phone' => '1122334455',
            'address' => '789 Sender St',
            'city' => 'Sender City',
            'state' => 'Sender State',
            'country' => 'Philippines',
            'postal_code' => '67890',
            'is_verified' => true,
            'role' => 'sender',
            'rating' => 4.0,
            'bio' => 'I need help delivering packages to various locations.',
        ]);

        // Create additional traveler users
        $additionalTravelers = [
            [
                'name' => 'Juan Dela Cruz',
                'email' => 'juan@pasabay.com',
                'password' => Hash::make('password'),
                'phone' => '09123456789',
                'address' => '123 Main St',
                'city' => 'Manila',
                'state' => 'Metro Manila',
                'country' => 'Philippines',
                'postal_code' => '1000',
                'is_verified' => true,
                'role' => 'traveler',
                'rating' => 4.8,
                'bio' => 'Frequent traveler between Manila and Cebu.',
            ],
            [
                'name' => 'Maria Santos',
                'email' => 'maria@pasabay.com',
                'password' => Hash::make('password'),
                'phone' => '09234567890',
                'address' => '456 Secondary St',
                'city' => 'Davao',
                'state' => 'Davao del Sur',
                'country' => 'Philippines',
                'postal_code' => '8000',
                'is_verified' => true,
                'role' => 'traveler',
                'rating' => 4.3,
                'bio' => 'I travel weekly between Davao and Manila for business.',
            ],
            [
                'name' => 'Pedro Reyes',
                'email' => 'pedro@pasabay.com',
                'password' => Hash::make('password'),
                'phone' => '09345678901',
                'address' => '789 Tertiary St',
                'city' => 'Baguio',
                'state' => 'Benguet',
                'country' => 'Philippines',
                'postal_code' => '2600',
                'is_verified' => true,
                'role' => 'traveler',
                'rating' => 4.6,
                'bio' => 'I regularly travel to different parts of the Philippines and can help deliver packages.',
            ],
        ];

        foreach ($additionalTravelers as $traveler) {
            User::create($traveler);
        }
    }
}
