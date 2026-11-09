<?php

namespace App\Exceptions;

/**
 * The request is well-formed but breaks a booking rule
 * (tee time outside the stay, more players than guests, ...).
 */
class InvalidBookingException extends BookingException
{
    public function errorCode(): string
    {
        return 'invalid_booking';
    }

    public function status(): int
    {
        return 422;
    }
}
