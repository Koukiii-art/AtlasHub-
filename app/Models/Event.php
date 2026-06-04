<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Review;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'date',
        'time',
        'price',
        'numberOfPlaces',
        'category',
        'location',
        'image',
        'user_id',
    ];

    protected $casts = [
        'date' => 'date',
        'price' => 'decimal:2',
        'numberOfPlaces' => 'integer',
    ];

    /**
     * Get the user that owns the event.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the bookings for the event.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Get the reviews for the event.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}