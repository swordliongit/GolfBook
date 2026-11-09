<?php

namespace Database\Factories;

use App\Models\GolfCourse;
use App\Models\TeeTime;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TeeTime> */
class TeeTimeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'golf_course_id' => GolfCourse::factory(),
            'starts_at' => now()->addDays(3)->setTime(9, 0),
            'capacity' => 4,
            'booked_players' => 0,
        ];
    }
}
