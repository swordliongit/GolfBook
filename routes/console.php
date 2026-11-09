<?php

use App\Models\GolfCourse;
use App\Models\Partner;
use App\Services\TeeSheetGenerator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('teesheet:generate {--days=60 : How many days ahead to open}', function (TeeSheetGenerator $generator) {
    $from = today()->toImmutable();
    $to = $from->addDays((int) $this->option('days'));

    foreach (GolfCourse::where('active', true)->get() as $course) {
        $created = $generator->generate($course, $from, $to);
        $this->info("{$course->name}: {$created} new tee times");
    }
})->purpose('Open tee times for all active courses');

Artisan::command('partner:create {name} {country}', function (string $name, string $country) {
    [$partner, $key] = Partner::register($name, $country);

    $this->info("Partner #{$partner->id} {$partner->name} created.");
    $this->warn("API key (shown only once): {$key}");
})->purpose('Register a tour operator and print its API key');

// Keep a rolling 60-day tee sheet open.
Schedule::command('teesheet:generate --days=60')->dailyAt('02:00');
