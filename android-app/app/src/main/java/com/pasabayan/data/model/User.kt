package com.pasabayan.data.model

/**
 * User model representing a user in the application
 */
data class User(
    val id: String,
    val name: String,
    val email: String,
    val phone: String,
    val profileImageUrl: String? = null,
    val role: UserRole = UserRole.SENDER,
    val rating: Float = 0f,
    val reviewCount: Int = 0,
    val createdAt: Long = System.currentTimeMillis()
)

enum class UserRole {
    SENDER, TRAVELER, BOTH
} 