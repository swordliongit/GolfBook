<?php

namespace Tests\Feature;

use App\Models\GolfCourse;
use App\Models\Hotel;
use App\Models\Season;
use App\Models\TeeTime;
use App\Services\PricingService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_seasonal_multiplier_is_applied_night_by_night(): void
    {
        $hotel = Hotel::factory()->create(['nightly_rate_cents' => 10000]);
        Season::create(['name' => 'Peak', 'starts_on' => '2027-03-01', 'ends_on' => '2027-05-31', 'multiplier' => 1.5]);

        // 27 Feb and 28 Feb at base price, 1 Mar and 2 Mar at peak price.
        $quote = app(PricingService::class)->quote(
            $hotel,
            CarbonImmutable::parse('2027-02-27'),
            CarbonImmutable::parse('2027-03-03'),
            guests: 1,
            teeTimes: collect(),
            rounds: [],
            transferLegs: 0,
        );

        $this->assertSame(10000 + 10000 + 15000 + 15000, $quote->hotelCents);
    }

    public function test_green_fees_use_the_season_of_the_round_day(): void
    {
        $hotel = Hotel::factory()->create();
        $course = GolfCourse::factory()->create(['green_fee_cents' => 10000]);
        $teeTime = TeeTime::factory()->for($course)->create(['starts_at' => '2027-03-02 10:00:00'])->load('golfCourse');
        Season::create(['name' => 'Peak', 'starts_on' => '2027-03-01', 'ends_on' => '2027-05-31', 'multiplier' => 1.35]);

        $quote = app(PricingService::class)->quote(
            $hotel,
            CarbonImmutable::parse('2027-02-28'),
            CarbonImmutable::parse('2027-03-04'),
            guests: 3,
            teeTimes: collect([$teeTime->id => $teeTime]),
            rounds: [$teeTime->id => 3],
            transferLegs: 0,
        );

        $this->assertSame(40500, $quote->golfCents);
        $this->assertSame([$teeTime->id => 40500], $quote->roundCents);
    }

    public function test_transfer_vehicle_is_chosen_by_group_size(): void
    {
        $pricing = app(PricingService::class);

        $this->assertSame(4500, $pricing->transferPrice(4));
        $this->assertSame(7000, $pricing->transferPrice(5));
        $this->assertSame(11000, $pricing->transferPrice(16));
    }
}
