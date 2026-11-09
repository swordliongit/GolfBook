<?php

namespace App\Exceptions;

use App\Enums\BookingStatus;
use App\Models\Booking;

class BookingStateException extends BookingException
{
    public static function cannot(string $action, Booking $booking): self
    {
        /** @var BookingStatus $status */
        $status = $booking->status;

        return new self("Booking {$booking->reference} is {$status->value} and cannot be {$action}.");
    }

    public function errorCode(): string
    {
        return 'invalid_state';
    }
}
