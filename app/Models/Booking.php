<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'user_id',
        'status',
        'quantity',
        'booking_date',
    ];

    protected $casts = [
        'booking_date' => 'datetime',
    ];

    /**
     * Get the event for the booking.
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the user for the booking.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}