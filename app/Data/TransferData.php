<?php

namespace App\Data;

use Carbon\CarbonImmutable;

final readonly class TransferData
{
    public function __construct(
        public CarbonImmutable $pickupAt,
        public ?string $flightNumber = null,
    ) {}

    /** @param array<string, mixed> $input */
    public static function fromArray(array $input): self
    {
        return new self(
            pickupAt: CarbonImmutable::parse($input['pickup_at']),
            flightNumber: isset($input['flight_number']) ? strtoupper($input['flight_number']) : null,
        );
    }
}
