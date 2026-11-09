<?php

namespace App\Services;

use App\Models\GolfCourse;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

/**
 * Opens tee times for the coming days. Safe to run repeatedly: existing slots
 * (and their bookings) are left untouched thanks to the unique (course, starts_at) key.
 */
class TeeSheetGenerator
{
    /** @return int number of new tee times created */
    public function generate(GolfCourse $course, CarbonImmutable $from, CarbonImmutable $to): int
    {
        $settings = config('golfbook.tee_sheet');
        $now = now();
        $rows = [];

        foreach (CarbonPeriod::create($from, $to) as $day) {
            $slot = CarbonImmutable::parse($day->toDateString().' '.$settings['first']);
            $last = CarbonImmutable::parse($day->toDateString().' '.$settings['last']);

            for (; $slot->lte($last); $slot = $slot->addMinutes($settings['interval_minutes'])) {
                $rows[] = [
                    'golf_course_id' => $course->id,
                    'starts_at' => $slot->toDateTimeString(),
                    'capacity' => $settings['capacity'],
                    'booked_players' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        $created = 0;
        // Batched multi-row inserts: one query per 500 slots instead of one per slot.
        foreach (array_chunk($rows, 500) as $chunk) {
            $created += DB::table('tee_times')->insertOrIgnore($chunk);
        }

        return $created;
    }
}
