<?php

namespace App\Http\Resources;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Booking */
class BookingResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'reference' => $this->reference,
            'status' => $this->status->value,
            'hotel' => [
                'id' => $this->hotel->id,
                'name' => $this->hotel->name,
            ],
            'check_in' => $this->check_in->toDateString(),
            'check_out' => $this->check_out->toDateString(),
            'nights' => $this->nights(),
            'guests' => $this->guests,
            'customer' => [
                'name' => $this->customer_name,
                'email' => $this->customer_email,
                'country' => $this->customer_country,
            ],
            'rounds' => $this->rounds->map(fn ($round) => [
                'tee_time_id' => $round->tee_time_id,
                'course' => $round->teeTime->golfCourse->name,
                'starts_at' => $round->teeTime->starts_at->toIso8601String(),
                'players' => $round->players,
                'green_fee' => $round->green_fee_total_cents / 100,
            ]),
            'transfers' => $this->transfers->map(fn ($transfer) => [
                'direction' => $transfer->direction->value,
                'flight_number' => $transfer->flight_number,
                'pickup_at' => $transfer->pickup_at->toIso8601String(),
                'passengers' => $transfer->passengers,
            ]),
            'price' => [
                'currency' => config('golfbook.currency'),
                'hotel' => $this->hotel_total_cents / 100,
                'golf' => $this->golf_total_cents / 100,
                'transfers' => $this->transfer_total_cents / 100,
                'total' => $this->total_cents / 100,
            ],
            'created_at' => $this->created_at->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
        ];
    }
}
