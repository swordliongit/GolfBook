<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Exceptions\BookingException;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function __construct(private readonly BookingService $bookings) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', 'in:'.implode(',', array_column(BookingStatus::cases(), 'value'))],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $bookings = Booking::with(['hotel', 'partner'])
            ->filter($filters)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.bookings.index', [
            'bookings' => $bookings,
            'filters' => $filters,
            'statuses' => BookingStatus::cases(),
        ]);
    }

    public function show(Booking $booking): View
    {
        $booking->load(['hotel', 'partner', 'rounds.teeTime.golfCourse', 'transfers']);

        return view('admin.bookings.show', compact('booking'));
    }

    public function confirm(Booking $booking): RedirectResponse
    {
        return $this->run(fn () => $this->bookings->confirm($booking), $booking, 'Booking confirmed.');
    }

    public function cancel(Booking $booking): RedirectResponse
    {
        return $this->run(fn () => $this->bookings->cancel($booking), $booking, 'Booking cancelled, tee time spots released.');
    }

    private function run(callable $action, Booking $booking, string $success): RedirectResponse
    {
        try {
            $action();
        } catch (BookingException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.bookings.show', $booking)->with('status', $success);
    }
}
