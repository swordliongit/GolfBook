<?php

namespace App\Http\Resources;

use App\Models\TeeTime;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TeeTime */
class TeeTimeResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'starts_at' => $this->starts_at->toIso8601String(),
            'time' => $this->starts_at->format('H:i'),
            'capacity' => $this->capacity,
            'free_spots' => $this->freeSpots(),
        ];
    }
}
