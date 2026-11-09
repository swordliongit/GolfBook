<?php

namespace Database\Seeders;

use App\Data\BookingData;
use App\Data\TransferData;
use App\Models\GolfCourse;
use App\Models\Hotel;
use App\Models\Partner;
use App\Models\Season;
use App\Models\TeeTime;
use App\Models\User;
use App\Services\BookingService;
use App\Services\TeeSheetGenerator;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Demo data for Belek: real hotel and course names, fictional guests and prices.
 */
class DatabaseSeeder extends Seeder
{
    /** Demo-only keys so the README examples work out of the box. Never use in production. */
    public const DEMO_KEYS = [
        'Fairway Golf Holidays' => ['GB', 'demo-uk-fairway-key-0000000000000000000000'],
        'Sonnengolf Reisen' => ['DE', 'demo-de-sonnengolf-key-00000000000000000000'],
    ];

    public function run(BookingService $bookings, TeeSheetGenerator $teeSheet): void
    {
        User::factory()->create([
            'name' => 'Operations',
            'email' => 'ops@golfbook.test',
            'password' => 'password',
        ]);

        foreach ([
            ['Regnum Carya', 5, 16500],
            ['Maxx Royal Belek Golf Resort', 5, 32000],
            ['Gloria Serenity Resort', 5, 14000],
            ['Cornelia Diamond Golf Resort', 5, 15500],
            ['Kaya Palazzo Golf Resort', 5, 12500],
        ] as [$name, $stars, $rate]) {
            Hotel::create(['name' => $name, 'slug' => Str::slug($name), 'region' => 'Belek', 'stars' => $stars, 'nightly_rate_cents' => $rate]);
        }

        foreach ([
            ['Carya Golf Club', 72, 14500],
            ['Montgomerie Maxx Royal', 72, 13500],
            ['Gloria Old Course', 72, 11000],
            ['Cornelia Faldo Course', 72, 12000],
            ['National Golf Club', 72, 12500],
            ['Lykia Links', 72, 13000],
        ] as [$name, $par, $fee]) {
            GolfCourse::create(['name' => $name, 'slug' => Str::slug($name), 'region' => 'Belek', 'par' => $par, 'green_fee_cents' => $fee]);
        }

        // Antalya golf seasons: spring and autumn are the peak, mid-summer is quiet.
        $year = (int) now()->format('Y');
        foreach ([$year, $year + 1] as $y) {
            Season::create(['name' => "Spring peak {$y}", 'starts_on' => "{$y}-03-01", 'ends_on' => "{$y}-05-31", 'multiplier' => 1.35]);
            Season::create(['name' => "Summer low {$y}", 'starts_on' => "{$y}-07-01", 'ends_on' => "{$y}-08-31", 'multiplier' => 0.80]);
            Season::create(['name' => "Autumn peak {$y}", 'starts_on' => "{$y}-09-15", 'ends_on' => "{$y}-11-15", 'multiplier' => 1.35]);
        }

        $from = today()->toImmutable();
        foreach (GolfCourse::all() as $course) {
            $teeSheet->generate($course, $from, $from->addDays(45));
        }

        $partners = [];
        foreach (self::DEMO_KEYS as $name => [$country, $key]) {
            $partners[] = Partner::create(['name' => $name, 'country' => $country, 'api_key_hash' => Partner::hashKey($key)]);
        }

        $this->seedBookings($bookings, $partners);
    }

    /** @param list<Partner> $partners */
    private function seedBookings(BookingService $bookings, array $partners): void
    {
        mt_srand(42);
        fake()->seed(42);

        // Pretend the bookings were made two days ago, so stays starting today are still bookable.
        Carbon::setTestNow(now()->subDays(2));

        $hotels = Hotel::all();
        $courses = GolfCourse::all();

        for ($i = 0; $i < 40; $i++) {
            $partner = $partners[array_rand($partners)];
            // The first bookings are already in-house today, the rest arrive over the next weeks.
            $checkIn = today()->toImmutable()->addDays($i < 12 ? mt_rand(1, 3) : mt_rand(1, 26));
            $nights = [3, 4, 5, 7][mt_rand(0, 3)];
            $checkOut = $checkIn->addDays($nights);
            $guests = [2, 2, 2, 3, 4, 4, 6, 8][mt_rand(0, 7)];
            $players = min($guests, 4);

            $rounds = [];
            for ($day = 1; $day < $nights && count($rounds) < 4; $day += 1 + mt_rand(0, 1)) {
                $teeTime = TeeTime::where('golf_course_id', $courses->random()->id)
                    ->whereDate('starts_at', $checkIn->addDays($day))
                    ->whereRaw('capacity - booked_players >= ?', [$players])
                    ->inRandomOrder()
                    ->first();
                if ($teeTime) {
                    $rounds[$teeTime->id] = $players;
                }
            }
            if ($rounds === []) {
                continue;
            }

            $german = $partner->country === 'DE';
            $people = $german ? fake('de_DE') : fake('en_GB');
            $booking = $bookings->create(new BookingData(
                hotelId: $hotels->random()->id,
                checkIn: $checkIn,
                checkOut: $checkOut,
                guests: $guests,
                customerName: $people->firstName().' '.$people->lastName(),
                customerEmail: fake()->unique()->safeEmail(),
                customerCountry: $partner->country,
                rounds: $rounds,
                arrival: new TransferData($checkIn->setTime(mt_rand(9, 20), [0, 15, 30, 45][mt_rand(0, 3)]), ($german ? 'XQ' : 'TOM').mt_rand(100, 999)),
                departure: new TransferData($checkOut->setTime(mt_rand(6, 16), 0), ($german ? 'XQ' : 'TOM').mt_rand(100, 999)),
            ), $partner);

            match (true) {
                $i % 7 === 0 => $bookings->cancel($booking),
                $i % 3 !== 0 => $bookings->confirm($booking),
                default => null, // left pending for the operations team
            };
        }

        Carbon::setTestNow();
    }
}
