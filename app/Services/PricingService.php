<?php

namespace App\Services;

use App\Data\Quote;
use App\Models\Hotel;
use App\Models\Season;
use App\Models\TeeTime;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Calculates the price of a golf holiday: hotel nights, green fees and airport transfers.
 *
 * Seasonal multipliers are applied per night and per round, so a stay that crosses
 * from low into high season is priced correctly day by day.
 */
class PricingService
{
    /**
     * @param  Collection<int, TeeTime>  $teeTimes  tee times keyed by id, with golfCourse loaded
     * @param  array<int, int>  $rounds  tee_time_id => players
     */
    public function quote(
        Hotel $hotel,
        CarbonImmutable $checkIn,
        CarbonImmutable $checkOut,
        int $guests,
        Collection $teeTimes,
        array $rounds,
        int $transferLegs,
    ): Quote {
        if ($checkOut->lessThanOrEqualTo($checkIn)) {
            throw new InvalidArgumentException('Check-out must be after check-in.');
        }

        // One query for every season overlapping the stay, instead of one per night.
        $seasons = Season::query()
            ->whereDate('starts_on', '<=', $checkOut)
            ->whereDate('ends_on', '>=', $checkIn)
            ->get();

        $price = fn (int $cents, CarbonInterface $day): int => (int) round($cents * $this->multiplier($seasons, $day));
        $lines = [];

        $hotelCents = 0;
        foreach (CarbonPeriod::create($checkIn, $checkOut->subDay()) as $night) {
            $hotelCents += $price($hotel->nightly_rate_cents * $guests, $night);
        }
        $nights = (int) $checkIn->diffInDays($checkOut);
        $lines[] = [
            'type' => 'hotel',
            'description' => "{$hotel->name}: {$nights} nights x {$guests} guests",
            'amount_cents' => $hotelCents,
        ];

        $roundCents = [];
        foreach ($rounds as $teeTimeId => $players) {
            $teeTime = $teeTimes->get($teeTimeId);
            $amount = $price($teeTime->golfCourse->green_fee_cents * $players, $teeTime->starts_at);
            $roundCents[$teeTimeId] = $amount;
            $lines[] = [
                'type' => 'golf',
                'description' => "{$teeTime->golfCourse->name} {$teeTime->starts_at->format('d.m.Y H:i')}, {$players} players",
                'amount_cents' => $amount,
            ];
        }

        $golfCents = array_sum($roundCents);
        $transferCents = $transferLegs * $this->transferPrice($guests);
        if ($transferLegs > 0) {
            $lines[] = [
                'type' => 'transfer',
                'description' => "Airport transfer: {$transferLegs} leg(s), {$guests} passengers",
                'amount_cents' => $transferCents,
            ];
        }

        return new Quote($hotelCents, $golfCents, $transferCents, $roundCents, $lines);
    }

    public function transferPrice(int $passengers): int
    {
        foreach (config('golfbook.transfer_prices') as $maxPassengers => $priceCents) {
            if ($passengers <= $maxPassengers) {
                return $priceCents;
            }
        }

        throw new InvalidArgumentException("No transfer vehicle for {$passengers} passengers.");
    }

    /**
     * Highest multiplier of the seasons covering the day; 1.00 when none does.
     *
     * @param  Collection<int, Season>  $seasons
     */
    private function multiplier(Collection $seasons, CarbonInterface $day): float
    {
        $date = $day->toDateString();

        return (float) ($seasons
            ->filter(fn (Season $s) => $s->starts_on->toDateString() <= $date && $s->ends_on->toDateString() >= $date)
            ->max('multiplier') ?? 1.0);
    }
}
