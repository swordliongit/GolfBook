<?php

namespace App\Enums;

enum TransferDirection: string
{
    case Arrival = 'arrival';     // Antalya Airport to hotel
    case Departure = 'departure'; // hotel to Antalya Airport

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
