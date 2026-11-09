<?php

namespace App\Http\Resources;

use App\Models\GolfCourse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin GolfCourse */
class GolfCourseResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'region' => $this->region,
            'holes' => $this->holes,
            'par' => $this->par,
            'green_fee' => $this->green_fee_cents / 100,
            'currency' => config('golfbook.currency'),
        ];
    }
}
