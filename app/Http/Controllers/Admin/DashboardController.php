<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingRound;
use App\Models\Transfer;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Daily operations sheet: who plays where today, who has to be picked up, and the key numbers.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $day = CarbonImmutable::parse($request->query('date', today()->toDateString()));
        $active = fn (Builder $q) => $q->where('status', '!=', BookingStatus::Cancelled);

        $rounds = BookingRound::with(['teeTime.golfCourse', 'booking.hotel'])
            ->whereHas('booking', $active)
            ->whereHas('teeTime', fn (Builder $q) => $q->whereDate('starts_at', $day))
            ->get()
            ->sortBy(fn (BookingRound $r) => $r->teeTime->starts_at)
            ->groupBy(fn (BookingRound $r) => $r->teeTime->golfCourse->name);

        $transfers = Transfer::with('booking.hotel')
            ->whereHas('booking', $active)
            ->whereDate('pickup_at', $day)
            ->orderBy('pickup_at')
            ->get();

        $monthStart = $day->startOfMonth();
        $stats = [
            'pending' => Booking::where('status', BookingStatus::Pending)->count(),
            'arrivals_today' => Booking::where($active)->whereDate('check_in', $day)->count(),
            'month_bookings' => Booking::where($active)->whereBetween('created_at', [$monthStart, $monthStart->endOfMonth()])->count(),
            'month_revenue_cents' => (int) Booking::where('status', BookingStatus::Confirmed)
                ->whereBetween('check_in', [$monthStart, $monthStart->endOfMonth()])
                ->sum('total_cents'),
        ];

        return view('admin.dashboard', compact('day', 'rounds', 'transfers', 'stats'));
    }
}
