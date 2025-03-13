<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trip extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'traveler_id',
        'origin',
        'destination',
        'travel_date',
        'return_date',
        'available_capacity',
        'transport_mode',
        'notes',
        'status', // 'active', 'completed', 'cancelled'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'travel_date' => 'datetime',
        'return_date' => 'datetime',
        'available_capacity' => 'float',
    ];

    /**
     * Get the traveler that owns the trip.
     */
    public function traveler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'traveler_id');
    }

    /**
     * Get the delivery requests for the trip.
     */
    public function deliveryRequests(): HasMany
    {
        return $this->hasMany(DeliveryRequest::class);
    }

    /**
     * Get the payments for the trip.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the reviews for the trip.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
