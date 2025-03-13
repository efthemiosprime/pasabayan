package com.pasabayan.data.model

/**
 * Trip model representing a trip posted by a traveler
 */
data class Trip(
    val id: String,
    val userId: String,
    val origin: String,
    val destination: String,
    val departureDate: Long,
    val arrivalDate: Long,
    val capacity: Float, // in kg
    val availableCapacity: Float, // in kg
    val price: Float, // base price per kg
    val notes: String? = null,
    val status: TripStatus = TripStatus.ACTIVE,
    val createdAt: Long = System.currentTimeMillis(),
    val updatedAt: Long = System.currentTimeMillis()
)

enum class TripStatus {
    ACTIVE, IN_PROGRESS, COMPLETED, CANCELLED
} 