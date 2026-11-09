<?php

namespace Database\Factories;

use App\Models\Partner;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Partner> */
class PartnerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company().' Golf Travel',
            'country' => 'GB',
            'api_key_hash' => Partner::hashKey(Str::random(40)),
            'active' => true,
        ];
    }

    /** Partner whose plain API key is known, for API tests. */
    public function withKey(string $plainKey): static
    {
        return $this->state(['api_key_hash' => Partner::hashKey($plainKey)]);
    }
}
