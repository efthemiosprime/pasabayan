<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\DeliveryRequest;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MatchController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Get delivery requests matching a trip.
     */
    public function getMatchingRequests(string $tripId)
    {
        $trip = Trip::findOrFail($tripId);

        // Find delivery requests that match the trip's destination
        $matchingRequests = DeliveryRequest::with('sender')
            ->where('status', 'pending')
            ->where(function ($query) use ($trip) {
                // Match by destination (simple text matching)
                $query->where('dropoff_location', 'like', '%' . $trip->destination . '%')
                    ->orWhere(DB::raw('LOWER(dropoff_location)'), 'like', '%' . strtolower($trip->destination) . '%');
            })
            ->whereDate('delivery_date', '>=', $trip->travel_date)
            ->orderBy('urgency', 'desc') // High urgency first
            ->paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => $matchingRequests
        ]);
    }

    /**
     * Get trips matching a delivery request.
     */
    public function getMatchingTrips(string $requestId)
    {
        $deliveryRequest = DeliveryRequest::findOrFail($requestId);

        // Find trips that match the delivery request's dropoff location
        $matchingTrips = Trip::with('traveler')
            ->where('status', 'active')
            ->where(function ($query) use ($deliveryRequest) {
                // Match by destination (simple text matching)
                $query->where('destination', 'like', '%' . $deliveryRequest->dropoff_location . '%')
                    ->orWhere(DB::raw('LOWER(destination)'), 'like', '%' . strtolower($deliveryRequest->dropoff_location) . '%');
            })
            ->whereDate('travel_date', '<=', $deliveryRequest->delivery_date)
            ->orderBy('travel_date', 'asc') // Earliest trips first
            ->paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => $matchingTrips
        ]);
    }

    /**
     * Get all potential matches (for admin use).
     */
    public function getAllMatches()
    {
        // Get active trips
        $trips = Trip::where('status', 'active')->get();
        
        // Get pending delivery requests
        $deliveryRequests = DeliveryRequest::where('status', 'pending')->get();
        
        $matches = [];
        
        // For each trip, find matching delivery requests
        foreach ($trips as $trip) {
            $matchingRequests = [];
            
            foreach ($deliveryRequests as $request) {
                // Simple matching logic (in a real app, this would be more sophisticated)
                $destinationMatch = stripos($request->dropoff_location, $trip->destination) !== false || 
                                   stripos($trip->destination, $request->dropoff_location) !== false;
                
                $dateMatch = strtotime($request->delivery_date) >= strtotime($trip->travel_date);
                
                if ($destinationMatch && $dateMatch) {
                    $matchingRequests[] = [
                        'request_id' => $request->id,
                        'sender_id' => $request->sender_id,
                        'pickup_location' => $request->pickup_location,
                        'dropoff_location' => $request->dropoff_location,
                        'package_size' => $request->package_size,
                        'urgency' => $request->urgency,
                        'delivery_date' => $request->delivery_date,
                    ];
                }
            }
            
            if (count($matchingRequests) > 0) {
                $matches[] = [
                    'trip_id' => $trip->id,
                    'traveler_id' => $trip->traveler_id,
                    'origin' => $trip->origin,
                    'destination' => $trip->destination,
                    'travel_date' => $trip->travel_date,
                    'matching_requests' => $matchingRequests
                ];
            }
        }
        
        return response()->json([
            'status' => 'success',
            'data' => $matches
        ]);
    }

    /**
     * Assign a delivery request to a trip.
     */
    public function assignRequestToTrip(Request $request)
    {
        $request->validate([
            'trip_id' => 'required|exists:trips,id',
            'request_id' => 'required|exists:delivery_requests,id'
        ]);

        $trip = Trip::findOrFail($request->trip_id);
        $deliveryRequest = DeliveryRequest::findOrFail($request->request_id);

        // Check if the trip is active
        if ($trip->status !== 'active') {
            return response()->json([
                'status' => 'error',
                'message' => 'The selected trip is not active'
            ], 400);
        }

        // Check if the delivery request is pending
        if ($deliveryRequest->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'The selected delivery request is not pending'
            ], 400);
        }

        // Assign the delivery request to the trip
        $deliveryRequest->update([
            'trip_id' => $trip->id,
            'estimated_cost' => $this->calculateEstimatedCost($deliveryRequest->package_size, $deliveryRequest->package_weight)
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Delivery request assigned to trip successfully',
            'data' => $deliveryRequest
        ]);
    }

    /**
     * Calculate estimated cost based on package size and weight.
     * This is a simplified version, in a real app this would be more complex.
     */
    private function calculateEstimatedCost($packageSize, $packageWeight)
    {
        $baseCost = [
            'small' => 10,
            'medium' => 20,
            'large' => 30,
            'extra_large' => 50,
        ];

        $weightMultiplier = 2; // $2 per kg

        return $baseCost[$packageSize] + ($packageWeight * $weightMultiplier);
    }
}
