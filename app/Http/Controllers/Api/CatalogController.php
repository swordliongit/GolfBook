<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GolfCourseResource;
use App\Http\Resources\TeeTimeResource;
use App\Models\GolfCourse;
use App\Models\Hotel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CatalogController extends Controller
{
    public function hotels(): JsonResponse
    {
        $hotels = Hotel::where('active', true)->orderBy('name')->get()
            ->map(fn (Hotel $hotel) => [
                'id' => $hotel->id,
                'name' => $hotel->name,
                'region' => $hotel->region,
                'stars' => $hotel->stars,
                'nightly_rate_per_person' => $hotel->nightly_rate_cents / 100,
                'currency' => config('golfbook.currency'),
            ]);

        return response()->json(['data' => $hotels]);
    }

    public function courses(): AnonymousResourceCollection
    {
        return GolfCourseResource::collection(
            GolfCourse::where('active', true)->orderBy('name')->get()
        );
    }

    /** Available tee times of a course for one day, optionally only those with enough free spots. */
    public function teeTimes(Request $request, GolfCourse $course): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'players' => ['sometimes', 'integer', 'min:1', 'max:4'],
        ]);

        abort_unless($course->active, 404);

        $players = (int) ($validated['players'] ?? 1);

        $teeTimes = $course->teeTimes()
            ->onDate($validated['date'])
            ->where('starts_at', '>', now())
            ->whereRaw('capacity - booked_players >= ?', [$players])
            ->get();

        return TeeTimeResource::collection($teeTimes);
    }
}
