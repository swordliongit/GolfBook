<?php

namespace App\Http\Requests\Api;

use App\Data\BookingData;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared by the quote and booking endpoints: same payload, different outcome.
 */
class BookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // the partner is already authenticated by middleware
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'hotel_id' => ['required', 'integer', 'exists:hotels,id'],
            'check_in' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'check_out' => ['required', 'date_format:Y-m-d', 'after:check_in'],
            'guests' => ['required', 'integer', 'min:1', 'max:'.config('golfbook.max_guests')],

            'customer.name' => ['required', 'string', 'max:120'],
            'customer.email' => ['required', 'email', 'max:190'],
            'customer.country' => ['required', 'string', 'size:2'],

            'rounds' => ['required', 'array', 'min:1', 'max:14'],
            'rounds.*.tee_time_id' => ['required', 'integer', 'distinct'],
            'rounds.*.players' => ['required', 'integer', 'min:1', 'max:4'],

            'arrival' => ['sometimes', 'array'],
            'arrival.pickup_at' => ['required_with:arrival', 'date'],
            'arrival.flight_number' => ['nullable', 'string', 'max:16'],
            'departure' => ['sometimes', 'array'],
            'departure.pickup_at' => ['required_with:departure', 'date'],
            'departure.flight_number' => ['nullable', 'string', 'max:16'],

            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function toData(): BookingData
    {
        return BookingData::fromArray($this->validated());
    }
}
