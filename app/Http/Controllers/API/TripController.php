<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TripController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Debug logging
        \Log::info('Trip search params:', $request->all());
        
        $query = Trip::with(['traveler' => function($query) {
            $query->select('id', 'name', 'email', 'phone', 'rating', 'is_verified');
        }]);

        // Filter by status (default to active if not specified)
        if ($request->has('status')) {
            \Log::info('Status filter:', ['status' => $request->status]);
            if ($request->status !== 'all' && $request->status !== 'upcoming') {
                $query->where('status', $request->status);
            } elseif ($request->status === 'upcoming') {
                // Map 'upcoming' to 'active' for backward compatibility
                $query->where('status', 'active');
            }
        } else {
            $query->where('status', 'active');
        }

        // Filter by origin
        if ($request->has('origin') && !empty($request->origin)) {
            \Log::info('Origin filter:', ['origin' => $request->origin]);
            $query->where('origin', 'like', '%' . $request->origin . '%');
        }

        // Filter by destination
        if ($request->has('destination') && !empty($request->destination)) {
            \Log::info('Destination filter:', ['destination' => $request->destination]);
            $query->where('destination', 'like', '%' . $request->destination . '%');
        }

        // Filter by date range
        if ($request->has('dateFrom') && !empty($request->dateFrom)) {
            $query->whereDate('travel_date', '>=', $request->dateFrom);
        }
        
        if ($request->has('dateTo') && !empty($request->dateTo)) {
            $query->whereDate('travel_date', '<=', $request->dateTo);
        }

        // Search by any field
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            \Log::info('Search term:', ['search' => $search]);
            $query->where(function($q) use ($search) {
                $q->where('origin', 'like', '%' . $search . '%')
                  ->orWhere('destination', 'like', '%' . $search . '%')
                  ->orWhere('transport_mode', 'like', '%' . $search . '%')
                  ->orWhere('notes', 'like', '%' . $search . '%');
            });
        }

        // Get trips
        $trips = $query->latest()->take(50)->get();
        \Log::info('Trips found:', ['count' => $trips->count()]);

        if ($trips->isEmpty()) {
            // If no trips found, try a broader search without filters
            if ($request->has('search') && !empty($request->search)) {
                \Log::info('No trips found with filters, trying broader search');
                $search = $request->search;
                $broadQuery = Trip::with(['traveler' => function($query) {
                    $query->select('id', 'name', 'email', 'phone', 'rating', 'is_verified');
                }])->where(function($q) use ($search) {
                    $q->where('origin', 'like', '%' . $search . '%')
                      ->orWhere('destination', 'like', '%' . $search . '%')
                      ->orWhere('transport_mode', 'like', '%' . $search . '%')
                      ->orWhere('notes', 'like', '%' . $search . '%');
                })->latest()->take(50)->get();
                
                if ($broadQuery->isNotEmpty()) {
                    $trips = $broadQuery;
                    \Log::info('Found trips with broader search:', ['count' => $trips->count()]);
                }
            }
        }

        // Format for frontend
        $formattedTrips = $trips->map(function($trip) {
            return [
                'id' => $trip->id,
                'traveler_id' => $trip->traveler_id,
                'traveler' => [
                    'id' => $trip->traveler->id,
                    'name' => $trip->traveler->name,
                    'rating' => $trip->traveler->rating ?? 0,
                    'profile_photo' => null, // Add profile photo handling when implemented
                    'email' => $trip->traveler->email,
                    'phone' => $trip->traveler->phone,
                    'is_verified' => $trip->traveler->is_verified
                ],
                'origin' => $trip->origin,
                'destination' => $trip->destination,
                'departure_date' => $trip->travel_date,
                'arrival_date' => $trip->return_date,
                'available_space' => $trip->available_capacity,
                'notes' => $trip->notes,
                'status' => str_replace(' ', '_', strtolower($trip->status)),
                'created_at' => $trip->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'trips' => $formattedTrips
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'origin' => 'required|string|max:255',
            'destination' => 'required|string|max:255',
            'travel_date' => 'required|date|after_or_equal:today',
            'return_date' => 'nullable|date|after_or_equal:travel_date',
            'available_capacity' => 'required|numeric|min:0.1',
            'transport_mode' => 'required|string|max:50',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if user is a traveler
        if (!$request->user()->isTraveler()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Only travelers can create trips'
            ], 403);
        }

        $trip = Trip::create([
            'traveler_id' => $request->user()->id,
            'origin' => $request->origin,
            'destination' => $request->destination,
            'travel_date' => $request->travel_date,
            'return_date' => $request->return_date,
            'available_capacity' => $request->available_capacity,
            'transport_mode' => $request->transport_mode,
            'notes' => $request->notes,
            'status' => 'active',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Trip created successfully',
            'data' => $trip
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $trip = Trip::with(['traveler', 'deliveryRequests' => function ($query) {
            $query->where('status', 'pending');
        }])->findOrFail($id);

        // Format for frontend
        $formattedTrip = [
            'id' => $trip->id,
            'traveler_id' => $trip->traveler_id,
            'traveler' => [
                'id' => $trip->traveler->id,
                'name' => $trip->traveler->name,
                'rating' => $trip->traveler->rating ?? 0,
                'profile_photo' => null, // Add profile photo handling when implemented
                'email' => $trip->traveler->email,
                'phone' => $trip->traveler->phone,
                'is_verified' => $trip->traveler->is_verified
            ],
            'origin' => $trip->origin,
            'destination' => $trip->destination,
            'departure_date' => $trip->travel_date,
            'arrival_date' => $trip->return_date,
            'available_space' => $trip->available_capacity,
            'notes' => $trip->notes,
            'status' => str_replace(' ', '_', strtolower($trip->status)),
            'created_at' => $trip->created_at
        ];

        return response()->json([
            'success' => true,
            'trip' => $formattedTrip
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'origin' => 'nullable|string|max:255',
            'destination' => 'nullable|string|max:255',
            'travel_date' => 'nullable|date|after_or_equal:today',
            'return_date' => 'nullable|date|after_or_equal:travel_date',
            'available_capacity' => 'nullable|numeric|min:0.1',
            'transport_mode' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'status' => 'nullable|in:active,completed,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $trip = Trip::findOrFail($id);

        // Check if user is the owner of the trip
        if ($request->user()->id !== $trip->traveler_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to update this trip'
            ], 403);
        }

        $trip->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Trip updated successfully',
            'data' => $trip
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $trip = Trip::findOrFail($id);

        // Check if user is the owner of the trip
        if ($request->user()->id !== $trip->traveler_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to delete this trip'
            ], 403);
        }

        // Check if trip has active delivery requests
        if ($trip->deliveryRequests()->whereIn('status', ['accepted', 'in_transit'])->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete trip with active delivery requests'
            ], 400);
        }

        $trip->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Trip deleted successfully'
        ]);
    }

    /**
     * Get trips created by the authenticated user.
     */
    public function myTrips(Request $request)
    {
        $query = Trip::with(['traveler' => function($query) {
            $query->select('id', 'name', 'email', 'phone', 'rating', 'is_verified');
        }])->where('traveler_id', $request->user()->id);
        
        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        
        // Sort options
        $sortBy = $request->input('sort_by', 'travel_date');
        $sortOrder = $request->input('sort_order', 'asc');
        
        $query->orderBy($sortBy, $sortOrder);
        
        $trips = $query->latest()->take(50)->get();
        
        // Format for frontend
        $formattedTrips = $trips->map(function($trip) {
            return [
                'id' => $trip->id,
                'traveler_id' => $trip->traveler_id,
                'traveler' => [
                    'id' => $trip->traveler->id,
                    'name' => $trip->traveler->name,
                    'rating' => $trip->traveler->rating ?? 0,
                    'profile_photo' => null,
                    'email' => $trip->traveler->email,
                    'phone' => $trip->traveler->phone,
                    'is_verified' => $trip->traveler->is_verified
                ],
                'origin' => $trip->origin,
                'destination' => $trip->destination,
                'departure_date' => $trip->travel_date,
                'arrival_date' => $trip->return_date,
                'available_space' => $trip->available_capacity,
                'notes' => $trip->notes,
                'status' => str_replace(' ', '_', strtolower($trip->status)),
                'created_at' => $trip->created_at,
                'requests_count' => $trip->deliveryRequests()->count()
            ];
        });

        return response()->json([
            'success' => true,
            'trips' => $formattedTrips
        ]);
    }

    /**
     * Get available trips for creating delivery requests.
     */
    public function availableTrips(Request $request)
    {
        // Get active trips with available capacity
        $query = Trip::with(['traveler' => function($query) {
            $query->select('id', 'name', 'email', 'rating', 'is_verified');
        }])
        ->where('status', 'active')
        ->where('travel_date', '>=', now())
        ->where('available_capacity', '>', 0);
        
        // Exclude trips created by the current user (senders can't request their own trips)
        $query->where('traveler_id', '!=', $request->user()->id);
        
        $trips = $query->orderBy('travel_date', 'asc')->get();
        
        // Format for frontend
        $formattedTrips = $trips->map(function($trip) {
            return [
                'id' => $trip->id,
                'traveler_id' => $trip->traveler_id,
                'traveler_name' => $trip->traveler->name,
                'origin' => $trip->origin,
                'destination' => $trip->destination,
                'travel_date' => $trip->travel_date,
                'return_date' => $trip->return_date,
                'available_capacity' => $trip->available_capacity
            ];
        });

        return response()->json($formattedTrips);
    }
}
