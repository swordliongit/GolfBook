<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\GolfCourse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * One course, one day: every tee time with its occupancy and the bookings in it.
 */
class TeeSheetController extends Controller
{
    public function __invoke(Request $request): View
    {
        $courses = GolfCourse::where('active', true)->orderBy('name')->get();

        $validated = $request->validate([
            'course' => ['nullable', 'exists:golf_courses,slug'],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $course = $courses->firstWhere('slug', $validated['course'] ?? null) ?? $courses->first();
        $date = $validated['date'] ?? today()->toDateString();

        $teeTimes = $course
            ? $course->teeTimes()
                ->onDate($date)
                ->with(['rounds' => fn ($q) => $q->whereHas('booking', fn (Builder $b) => $b->where('status', '!=', BookingStatus::Cancelled)),
                    'rounds.booking'])
                ->get()
            : collect();

        return view('admin.tee-sheet', compact('courses', 'course', 'date', 'teeTimes'));
    }
}
