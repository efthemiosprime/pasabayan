package com.pasabayan.data.model

/**
 * DeliveryRequest model representing a delivery request posted by a sender
 */
data class DeliveryRequest(
    val id: String,
    val userId: String,
    val pickupLocation: String,
    val dropoffLocation: String,
    val packageDescription: String,
    val packageSize: PackageSize,
    val packageWeight: Float, // in kg
    val packageValue: Float? = null, // optional value of the package
    val preferredDeliveryDate: Long? = null, // optional preferred delivery date
    val offerPrice: Float, // price offered by sender
    val specialInstructions: String? = null,
    val status: DeliveryRequestStatus = DeliveryRequestStatus.PENDING,
    val tripId: String? = null, // assigned trip ID if matched
    val travelerId: String? = null, // assigned traveler ID if matched
    val createdAt: Long = System.currentTimeMillis(),
    val updatedAt: Long = System.currentTimeMillis()
)

enum class PackageSize {
    SMALL, MEDIUM, LARGE
}

enum class DeliveryRequestStatus {
    PENDING, MATCHED, IN_TRANSIT, DELIVERED, CANCELLED
} 