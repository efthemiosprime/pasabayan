<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\DeliveryRequest;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DeliveryRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = DeliveryRequest::with(['trip', 'sender']);
        
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        
        $requests = $query->latest()->get();
        
        return response()->json([
            'success' => true,
            'requests' => $requests
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'trip_id' => 'required|exists:trips,id',
            'item_description' => 'required|string|max:255',
            'pickup_location' => 'required|string|max:255',
            'dropoff_location' => 'required|string|max:255',
            'package_size' => 'required|string|max:50',
            'special_instructions' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Get the authenticated user (sender)
        $user = Auth::user();
        
        // Check if user is a sender
        if ($user->role !== 'sender') {
            return response()->json([
                'success' => false,
                'message' => 'Only senders can create delivery requests'
            ], 403);
        }

        // Get the trip
        $trip = Trip::findOrFail($request->trip_id);
        
        // Check if trip is active or upcoming
        if ($trip->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Delivery requests can only be made for active trips'
            ], 400);
        }
        
        // Package size check - modified to handle string values
        $sizeMap = [
            'small' => 1,
            'medium' => 2,
            'large' => 3
        ];
        
        $packageSizeNumeric = $sizeMap[$request->package_size] ?? 1;
        
        if ($packageSizeNumeric > $trip->available_capacity) {
            return response()->json([
                'success' => false,
                'message' => 'Package size exceeds available space',
                'errors' => [
                    'package_size' => 'Package size exceeds the available space on this trip'
                ]
            ], 400);
        }

        // Create the delivery request
        $deliveryRequest = DeliveryRequest::create([
            'trip_id' => $request->trip_id,
            'sender_id' => $user->id,
            'item_description' => $request->item_description,
            'pickup_location' => $request->pickup_location,
            'delivery_location' => $request->dropoff_location,
            'package_size' => $packageSizeNumeric,
            'special_instructions' => $request->special_instructions,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Delivery request created successfully',
            'request' => $deliveryRequest
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $deliveryRequest = DeliveryRequest::with(['trip', 'sender'])->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'request' => $deliveryRequest
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Find the delivery request
        $deliveryRequest = DeliveryRequest::findOrFail($id);
        
        // Check if user owns this request or is the trip owner
        $user = Auth::user();
        if ($user->id !== $deliveryRequest->sender_id && $user->id !== $deliveryRequest->trip->traveler_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to update this request'
            ], 403);
        }

        // Validate the request
        $validator = Validator::make($request->all(), [
            'item_description' => 'sometimes|string|max:255',
            'pickup_location' => 'sometimes|string|max:255',
            'delivery_location' => 'sometimes|string|max:255',
            'package_size' => 'sometimes|numeric|min:0.1',
            'special_instructions' => 'nullable|string',
            'status' => 'sometimes|in:pending,accepted,rejected,completed,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Only allow status updates for trip owner
        if ($request->has('status') && $user->id !== $deliveryRequest->trip->traveler_id) {
            return response()->json([
                'success' => false,
                'message' => 'Only the trip owner can update the status'
            ], 403);
        }
        
        // Only allow other field updates for sender (if status is pending)
        if (($request->has('item_description') || $request->has('pickup_location') || 
             $request->has('delivery_location') || $request->has('package_size') || 
             $request->has('special_instructions')) && 
            ($user->id !== $deliveryRequest->sender_id || $deliveryRequest->status !== 'pending')) {
            return response()->json([
                'success' => false,
                'message' => 'Request details can only be updated by the sender when the status is pending'
            ], 400);
        }

        // Update the fields
        $updateData = [];
        
        if ($request->has('item_description')) {
            $updateData['item_description'] = $request->item_description;
        }
        
        if ($request->has('pickup_location')) {
            $updateData['pickup_location'] = $request->pickup_location;
        }
        
        if ($request->has('delivery_location')) {
            $updateData['delivery_location'] = $request->delivery_location;
        }
        
        if ($request->has('package_size')) {
            // Check if package size fits available space (only if being updated)
            if ($request->package_size > $deliveryRequest->trip->available_capacity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Package size exceeds available space',
                    'errors' => [
                        'package_size' => 'Package size exceeds the available space on this trip'
                    ]
                ], 400);
            }
            
            $updateData['package_size'] = $request->package_size;
        }
        
        if ($request->has('special_instructions')) {
            $updateData['special_instructions'] = $request->special_instructions;
        }
        
        if ($request->has('status')) {
            $updateData['status'] = $request->status;
        }
        
        // Update the delivery request
        $deliveryRequest->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Delivery request updated successfully',
            'request' => $deliveryRequest
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $deliveryRequest = DeliveryRequest::findOrFail($id);
        
        // Check if user owns this request
        $user = Auth::user();
        if ($user->id !== $deliveryRequest->sender_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to delete this request'
            ], 403);
        }
        
        // Only allow deletion if status is pending
        if ($deliveryRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Requests can only be deleted when in pending status'
            ], 400);
        }
        
        $deliveryRequest->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Delivery request deleted successfully'
        ]);
    }
    
    /**
     * Get delivery requests for the authenticated user
     */
    public function myDeliveryRequests(Request $request)
    {
        $user = Auth::user();
        
        $query = DeliveryRequest::with(['trip.traveler'])
            ->where('sender_id', $user->id);
            
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        
        $requests = $query->latest()->get();
        
        return response()->json([
            'success' => true,
            'requests' => $requests
        ]);
    }
    
    /**
     * Accept a delivery request (for travelers only)
     */
    public function acceptRequest(Request $request, string $id)
    {
        $deliveryRequest = DeliveryRequest::with(['trip.traveler'])->find($id);
        
        if (!$deliveryRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Delivery request not found'
            ], 404);
        }
        
        if ($deliveryRequest->trip->traveler_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Only the trip owner can accept requests'
            ], 403);
        }
        
        if ($deliveryRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending requests can be accepted'
            ], 400);
        }
        
        $deliveryRequest->update([
            'status' => 'accepted'
        ]);
        
        // Update trip available space/capacity if needed
        
        return response()->json([
            'success' => true,
            'message' => 'Delivery request accepted successfully',
            'request' => $deliveryRequest->fresh()
        ]);
    }

    /**
     * Cancel a delivery request
     */
    public function cancelRequest(Request $request, string $id)
    {
        $deliveryRequest = DeliveryRequest::find($id);
        
        if (!$deliveryRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Delivery request not found'
            ], 404);
        }
        
        // Only the sender or traveler can cancel
        if ($deliveryRequest->sender_id !== Auth::id() && 
            $deliveryRequest->trip->traveler_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to cancel this request'
            ], 403);
        }
        
        // Can only cancel pending or accepted requests
        if (!in_array($deliveryRequest->status, ['pending', 'accepted'])) {
            return response()->json([
                'success' => false,
                'message' => 'This request cannot be cancelled in its current state'
            ], 400);
        }
        
        // Validate cancellation reason
        $validator = Validator::make($request->all(), [
            'cancellation_reason' => 'required|string|min:5|max:255',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $deliveryRequest->update([
            'status' => 'cancelled',
            'special_instructions' => $request->cancellation_reason
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Delivery request cancelled successfully',
            'request' => $deliveryRequest->fresh()
        ]);
    }
    
    /**
     * Mark a delivery request as completed
     */
    public function completeRequest(Request $request, string $id)
    {
        $deliveryRequest = DeliveryRequest::find($id);
        
        if (!$deliveryRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Delivery request not found'
            ], 404);
        }
        
        // Only the sender can mark as completed
        if ($deliveryRequest->sender_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Only the sender can mark a request as completed'
            ], 403);
        }
        
        // Can only complete accepted requests
        if ($deliveryRequest->status !== 'accepted') {
            return response()->json([
                'success' => false,
                'message' => 'Only accepted requests can be marked as completed'
            ], 400);
        }
        
        $deliveryRequest->update([
            'status' => 'completed'
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Delivery request marked as completed successfully',
            'request' => $deliveryRequest->fresh()
        ]);
    }
}
