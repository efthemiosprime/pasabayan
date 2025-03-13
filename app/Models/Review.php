<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'reviewer_id',
        'reviewee_id',
        'delivery_request_id',
        'trip_id',
        'rating',
        'comment',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'rating' => 'float',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted()
    {
        // Update reviewee's average rating after a review is created
        static::created(function ($review) {
            $review->updateRevieweeRating();
        });

        // Update reviewee's average rating after a review is updated
        static::updated(function ($review) {
            $review->updateRevieweeRating();
        });

        // Update reviewee's average rating after a review is deleted
        static::deleted(function ($review) {
            $review->updateRevieweeRating();
        });
    }

    /**
     * Update the reviewee's average rating.
     */
    public function updateRevieweeRating()
    {
        $reviewee = User::find($this->reviewee_id);
        if ($reviewee) {
            $averageRating = Review::where('reviewee_id', $this->reviewee_id)->avg('rating') ?? 0;
            $reviewee->update(['rating' => $averageRating]);
        }
    }

    /**
     * Get the reviewer that owns the review.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * Get the reviewee that receives the review.
     */
    public function reviewee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewee_id');
    }

    /**
     * Get the delivery request associated with the review.
     */
    public function deliveryRequest(): BelongsTo
    {
        return $this->belongsTo(DeliveryRequest::class);
    }

    /**
     * Get the trip associated with the review.
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}
