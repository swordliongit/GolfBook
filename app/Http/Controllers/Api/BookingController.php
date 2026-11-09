<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\BookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Partner;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(private readonly BookingService $bookings) {}

    public function quote(BookingRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->bookings->quote($request->toData())->toArray()]);
    }

    public function store(BookingRequest $request): JsonResponse
    {
        $booking = $this->bookings->create($request->toData(), $this->partner($request));

        return (new BookingResource($booking))->response()->setStatusCode(201);
    }

    public function show(Request $request, string $reference): BookingResource
    {
        return new BookingResource($this->findOwn($request, $reference));
    }

    public function destroy(Request $request, string $reference): BookingResource
    {
        $booking = $this->bookings->cancel($this->findOwn($request, $reference));

        return new BookingResource($booking->load(['hotel', 'rounds.teeTime.golfCourse', 'transfers']));
    }

    private function partner(Request $request): Partner
    {
        return $request->attributes->get('partner');
    }

    /**
     * A partner can only see its own bookings. Someone else's reference returns 404,
     * not 403, so references of other partners cannot be discovered by guessing.
     */
    private function findOwn(Request $request, string $reference): Booking
    {
        return Booking::with(['hotel', 'rounds.teeTime.golfCourse', 'transfers'])
            ->where('reference', $reference)
            ->where('partner_id', $this->partner($request)->id)
            ->firstOrFail();
    }
}
