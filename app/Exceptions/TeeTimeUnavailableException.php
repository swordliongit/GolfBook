<?php

namespace App\Exceptions;

use App\Models\TeeTime;

class TeeTimeUnavailableException extends BookingException
{
    public static function for(TeeTime $teeTime, int $requested): self
    {
        return new self(sprintf(
            '%s at %s has %d free spot(s), %d requested.',
            $teeTime->golfCourse->name,
            $teeTime->starts_at->format('d.m.Y H:i'),
            $teeTime->freeSpots(),
            $requested,
        ));
    }

    public function errorCode(): string
    {
        return 'tee_time_unavailable';
    }
}
