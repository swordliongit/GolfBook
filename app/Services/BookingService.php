<?php

namespace App\Services;

use App\Data\BookingData;
use App\Data\Quote;
use App\Enums\BookingStatus;
use App\Enums\TransferDirection;
use App\Exceptions\BookingStateException;
use App\Exceptions\InvalidBookingException;
use App\Exceptions\TeeTimeUnavailableException;
use App\Models\Booking;
use App\Models\Hotel;
use App\Models\Partner;
use App\Models\TeeTime;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BookingService
{
    public function __construct(private readonly PricingService $pricing) {}

    /** Price a holiday without reserving anything. */
    public function quote(BookingData $data): Quote
    {
        $hotel = Hotel::where('active', true)->findOrFail($data->hotelId);
        $teeTimes = $this->teeTimesFor($data, lock: false);

        return $this->pricing->quote(
            $hotel, $data->checkIn, $data->checkOut, $data->guests,
            $teeTimes, $data->rounds, $data->transferLegs(),
        );
    }

    /**
     * Creates the booking and reserves the tee time spots atomically.
     *
     * The tee time rows are locked (SELECT ... FOR UPDATE) for the duration of the
     * transaction, so two partners booking the last spots of the same slot at the
     * same moment cannot both succeed. Rows are locked in id order to avoid deadlocks,
     * and the transaction is retried if MySQL still reports one.
     */
    public function create(BookingData $data, ?Partner $partner = null): Booking
    {
        return DB::transaction(function () use ($data, $partner) {
            $hotel = Hotel::where('active', true)->findOrFail($data->hotelId);
            $teeTimes = $this->teeTimesFor($data, lock: true);

            foreach ($data->rounds as $teeTimeId => $players) {
                $teeTime = $teeTimes->get($teeTimeId);
                if ($teeTime->freeSpots() < $players) {
                    throw TeeTimeUnavailableException::for($teeTime, $players);
                }
            }

            $quote = $this->pricing->quote(
                $hotel, $data->checkIn, $data->checkOut, $data->guests,
                $teeTimes, $data->rounds, $data->transferLegs(),
            );

            $booking = Booking::create([
                'reference' => $this->newReference(),
                'status' => BookingStatus::Pending,
                'partner_id' => $partner?->id,
                'hotel_id' => $hotel->id,
                'customer_name' => $data->customerName,
                'customer_email' => $data->customerEmail,
                'customer_country' => $data->customerCountry,
                'check_in' => $data->checkIn,
                'check_out' => $data->checkOut,
                'guests' => $data->guests,
                'hotel_total_cents' => $quote->hotelCents,
                'golf_total_cents' => $quote->golfCents,
                'transfer_total_cents' => $quote->transferCents,
                'total_cents' => $quote->totalCents(),
                'notes' => $data->notes,
            ]);

            foreach ($data->rounds as $teeTimeId => $players) {
                $teeTimes->get($teeTimeId)->increment('booked_players', $players);
                $booking->rounds()->create([
                    'tee_time_id' => $teeTimeId,
                    'players' => $players,
                    'green_fee_total_cents' => $quote->roundCents[$teeTimeId],
                ]);
            }

            $legPrice = $data->transferLegs() > 0 ? $this->pricing->transferPrice($data->guests) : 0;
            foreach ([TransferDirection::Arrival->value => $data->arrival, TransferDirection::Departure->value => $data->departure] as $direction => $transfer) {
                if ($transfer) {
                    $booking->transfers()->create([
                        'direction' => $direction,
                        'flight_number' => $transfer->flightNumber,
                        'pickup_at' => $transfer->pickupAt,
                        'passengers' => $data->guests,
                        'price_cents' => $legPrice,
                    ]);
                }
            }

            return $booking->load(['hotel', 'rounds.teeTime.golfCourse', 'transfers']);
        }, attempts: 3);
    }

    public function confirm(Booking $booking): Booking
    {
        if ($booking->status !== BookingStatus::Pending) {
            throw BookingStateException::cannot('confirmed', $booking);
        }

        $booking->update(['status' => BookingStatus::Confirmed, 'confirmed_at' => now()]);

        return $booking;
    }

    /** Cancels the booking and gives its tee time spots back. */
    public function cancel(Booking $booking): Booking
    {
        return DB::transaction(function () use ($booking) {
            // Re-read the booking under lock so a double click cannot release spots twice.
            $booking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if (! $booking->status->isActive()) {
                throw BookingStateException::cannot('cancelled', $booking);
            }

            $rounds = $booking->rounds()->get();
            $teeTimes = TeeTime::whereIn('id', $rounds->pluck('tee_time_id'))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($rounds as $round) {
                $teeTimes->get($round->tee_time_id)->decrement('booked_players', $round->players);
            }

            $booking->update(['status' => BookingStatus::Cancelled, 'cancelled_at' => now()]);

            return $booking;
        }, attempts: 3);
    }

    /**
     * Loads the requested tee times and checks the rules that do not depend on availability.
     *
     * @return Collection<int, TeeTime>
     */
    private function teeTimesFor(BookingData $data, bool $lock): Collection
    {
        if ($data->checkOut->lessThanOrEqualTo($data->checkIn)) {
            throw new InvalidBookingException('Check-out must be after check-in.');
        }

        $query = TeeTime::with('golfCourse')
            ->whereIn('id', array_keys($data->rounds))
            ->orderBy('id');

        $teeTimes = ($lock ? $query->lockForUpdate() : $query)->get()->keyBy('id');

        foreach ($data->rounds as $teeTimeId => $players) {
            $teeTime = $teeTimes->get($teeTimeId);

            if (! $teeTime || ! $teeTime->golfCourse->active) {
                throw new InvalidBookingException("Tee time {$teeTimeId} does not exist.");
            }
            if ($teeTime->starts_at->isPast()) {
                throw new InvalidBookingException("Tee time {$teeTimeId} is in the past.");
            }
            if ($teeTime->starts_at->lt($data->checkIn) || $teeTime->starts_at->gte($data->checkOut->endOfDay())) {
                throw new InvalidBookingException("Tee time {$teeTimeId} is outside the stay.");
            }
            if ($players > $data->guests) {
                throw new InvalidBookingException("Round at tee time {$teeTimeId} has more players than guests.");
            }
        }

        return $teeTimes;
    }

    /** Short, human-readable reference such as BG26-7KQ4XM (no 0/O or 1/I to avoid typos on the phone). */
    private function newReference(): string
    {
        $alphabet = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

        do {
            $code = '';
            for ($i = 0; $i < 6; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $reference = 'BG'.now()->format('y').'-'.$code;
        } while (Booking::where('reference', $reference)->exists());

        return $reference;
    }
}
