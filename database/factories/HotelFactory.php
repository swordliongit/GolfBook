<?php

namespace Database\Factories;

use App\Models\Hotel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Hotel> */
class HotelFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->company().' Resort';

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'region' => 'Belek',
            'stars' => 5,
            'nightly_rate_cents' => 10000,
            'active' => true,
        ];
    }
}
