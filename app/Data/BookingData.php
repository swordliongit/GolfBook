<?php

namespace App\Data;

use Carbon\CarbonImmutable;

/**
 * Validated input for a new booking, independent of where it came from (API or admin).
 */
final readonly class BookingData
{
    /**
     * @param  array<int, int>  $rounds  tee_time_id => players
     */
    public function __construct(
        public int $hotelId,
        public CarbonImmutable $checkIn,
        public CarbonImmutable $checkOut,
        public int $guests,
        public string $customerName,
        public string $customerEmail,
        public string $customerCountry,
        public array $rounds,
        public ?TransferData $arrival = null,
        public ?TransferData $departure = null,
        public ?string $notes = null,
    ) {}

    /** @param array<string, mixed> $input  output of a FormRequest's validated() */
    public static function fromArray(array $input): self
    {
        $rounds = [];
        foreach ($input['rounds'] ?? [] as $round) {
            $rounds[(int) $round['tee_time_id']] = (int) $round['players'];
        }

        return new self(
            hotelId: (int) $input['hotel_id'],
            checkIn: CarbonImmutable::parse($input['check_in'])->startOfDay(),
            checkOut: CarbonImmutable::parse($input['check_out'])->startOfDay(),
            guests: (int) $input['guests'],
            customerName: $input['customer']['name'],
            customerEmail: $input['customer']['email'],
            customerCountry: strtoupper($input['customer']['country']),
            rounds: $rounds,
            arrival: isset($input['arrival']) ? TransferData::fromArray($input['arrival']) : null,
            departure: isset($input['departure']) ? TransferData::fromArray($input['departure']) : null,
            notes: $input['notes'] ?? null,
        );
    }

    public function transferLegs(): int
    {
        return ($this->arrival ? 1 : 0) + ($this->departure ? 1 : 0);
    }
}
