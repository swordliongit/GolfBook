<?php

namespace App\Models;

use App\Enums\TransferDirection;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['booking_id', 'direction', 'flight_number', 'pickup_at', 'passengers', 'price_cents'])]
class Transfer extends Model
{
    protected function casts(): array
    {
        return [
            'direction' => TransferDirection::class,
            'pickup_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
