<?php

namespace Database\Factories;

use App\Models\GolfCourse;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<GolfCourse> */
class GolfCourseFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->lastName().' Golf Club';

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'region' => 'Belek',
            'holes' => 18,
            'par' => 72,
            'green_fee_cents' => 8000,
            'active' => true,
        ];
    }
}
