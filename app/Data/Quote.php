<?php

namespace App\Data;

/**
 * Immutable price breakdown. All amounts are in euro cents.
 */
final readonly class Quote
{
    /**
     * @param  array<int, int>  $roundCents  tee_time_id => green fee total for that round
     * @param  list<array{type: string, description: string, amount_cents: int}>  $lines
     */
    public function __construct(
        public int $hotelCents,
        public int $golfCents,
        public int $transferCents,
        public array $roundCents,
        public array $lines,
    ) {}

    public function totalCents(): int
    {
        return $this->hotelCents + $this->golfCents + $this->transferCents;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'currency' => config('golfbook.currency'),
            'hotel' => $this->hotelCents / 100,
            'golf' => $this->golfCents / 100,
            'transfers' => $this->transferCents / 100,
            'total' => $this->totalCents() / 100,
            'lines' => array_map(fn (array $line) => [
                'type' => $line['type'],
                'description' => $line['description'],
                'amount' => $line['amount_cents'] / 100,
            ], $this->lines),
        ];
    }
}
