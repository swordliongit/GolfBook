<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['booking_id', 'tee_time_id', 'players', 'green_fee_total_cents'])]
class BookingRound extends Model
{
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function teeTime(): BelongsTo
    {
        return $this->belongsTo(TeeTime::class);
    }
}
