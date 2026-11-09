<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\GolfCourse;
use App\Models\Hotel;
use App\Models\Partner;
use App\Models\TeeTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingApiTest extends TestCase
{
    use RefreshDatabase;

    private const KEY = 'test-partner-key';

    private Partner $partner;

    private Hotel $hotel;

    private TeeTime $teeTime;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(now()->setDate(2027, 1, 10)->setTime(12, 0)); // outside any season

        $this->partner = Partner::factory()->withKey(self::KEY)->create();
        $this->hotel = Hotel::factory()->create(['nightly_rate_cents' => 10000]);
        $course = GolfCourse::factory()->create(['green_fee_cents' => 8000]);
        $this->teeTime = TeeTime::factory()->for($course)->create(['starts_at' => '2027-01-21 09:00:00']);
    }

    private function payload(array $overrides = []): array
    {
        return array_replace_recursive([
            'hotel_id' => $this->hotel->id,
            'check_in' => '2027-01-20',
            'check_out' => '2027-01-23',
            'guests' => 2,
            'customer' => ['name' => 'James Carter', 'email' => 'james@example.co.uk', 'country' => 'gb'],
            'rounds' => [['tee_time_id' => $this->teeTime->id, 'players' => 2]],
            'arrival' => ['pickup_at' => '2027-01-20 14:30', 'flight_number' => 'tom1234'],
        ], $overrides);
    }

    private function api(): static
    {
        return $this->withHeader('X-Api-Key', self::KEY);
    }

    public function test_requests_without_a_valid_key_are_rejected(): void
    {
        $this->getJson('/api/v1/courses')->assertUnauthorized();
        $this->withHeader('X-Api-Key', 'wrong')->getJson('/api/v1/courses')->assertUnauthorized();
    }

    public function test_inactive_partners_are_rejected(): void
    {
        $this->partner->update(['active' => false]);

        $this->api()->getJson('/api/v1/courses')->assertUnauthorized();
    }

    public function test_it_lists_tee_times_with_enough_free_spots(): void
    {
        $this->teeTime->update(['booked_players' => 3]);

        $this->api()->getJson("/api/v1/courses/{$this->teeTime->golfCourse->slug}/tee-times?date=2027-01-21")
            ->assertOk()
            ->assertJsonPath('data.0.free_spots', 1);

        $this->api()->getJson("/api/v1/courses/{$this->teeTime->golfCourse->slug}/tee-times?date=2027-01-21&players=2")
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_it_creates_a_booking_and_reserves_the_spots(): void
    {
        $response = $this->api()->postJson('/api/v1/bookings', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.nights', 3)
            ->assertJsonPath('data.customer.country', 'GB')
            ->assertJsonPath('data.transfers.0.flight_number', 'TOM1234')
            // 3 nights x 2 guests x 100 + 2 players x 80 + one transfer leg for 2 (car, 45)
            ->assertJsonPath('data.price.total', 805);

        $this->assertMatchesRegularExpression('/^BG\d{2}-[2-9A-HJ-NP-Z]{6}$/', $response->json('data.reference'));
        $this->assertSame(2, $this->teeTime->fresh()->booked_players);
        $this->assertSame($this->partner->id, Booking::first()->partner_id);
    }

    public function test_it_refuses_to_overbook_a_tee_time(): void
    {
        $this->teeTime->update(['booked_players' => 3]);

        $this->api()->postJson('/api/v1/bookings', $this->payload())
            ->assertStatus(409)
            ->assertJsonPath('error', 'tee_time_unavailable');

        $this->assertSame(3, $this->teeTime->fresh()->booked_players);
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_a_failed_round_rolls_back_the_whole_booking(): void
    {
        $full = TeeTime::factory()->for($this->teeTime->golfCourse)->create([
            'starts_at' => '2027-01-22 10:00:00',
            'booked_players' => 4,
        ]);

        $this->api()->postJson('/api/v1/bookings', $this->payload([
            'rounds' => [
                ['tee_time_id' => $this->teeTime->id, 'players' => 2],
                ['tee_time_id' => $full->id, 'players' => 2],
            ],
        ]))->assertStatus(409);

        $this->assertSame(0, $this->teeTime->fresh()->booked_players);
        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseCount('booking_rounds', 0);
    }

    public function test_tee_times_must_fall_inside_the_stay(): void
    {
        $this->api()->postJson('/api/v1/bookings', $this->payload(['check_in' => '2027-01-22', 'check_out' => '2027-01-25']))
            ->assertStatus(422)
            ->assertJsonPath('error', 'invalid_booking');
    }

    public function test_it_validates_the_payload(): void
    {
        $this->api()->postJson('/api/v1/bookings', $this->payload([
            'check_out' => '2027-01-19',
            'guests' => 0,
            'customer' => ['email' => 'not-an-email'],
            'rounds' => [['tee_time_id' => $this->teeTime->id, 'players' => 5]],
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['check_out', 'guests', 'customer.email', 'rounds.0.players']);
    }

    public function test_quote_prices_without_reserving(): void
    {
        $this->api()->postJson('/api/v1/quotes', $this->payload())
            ->assertOk()
            ->assertJsonPath('data.hotel', 600)
            ->assertJsonPath('data.golf', 160)
            ->assertJsonPath('data.transfers', 45)
            ->assertJsonPath('data.total', 805);

        $this->assertSame(0, $this->teeTime->fresh()->booked_players);
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_partners_cannot_see_each_others_bookings(): void
    {
        $reference = $this->api()->postJson('/api/v1/bookings', $this->payload())->json('data.reference');

        Partner::factory()->withKey('other-key')->create();

        $this->withHeader('X-Api-Key', 'other-key')->getJson("/api/v1/bookings/{$reference}")->assertNotFound();
        $this->api()->getJson("/api/v1/bookings/{$reference}")->assertOk()->assertJsonPath('data.reference', $reference);
    }

    public function test_cancelling_releases_the_spots_and_cannot_happen_twice(): void
    {
        $reference = $this->api()->postJson('/api/v1/bookings', $this->payload())->json('data.reference');

        $this->api()->deleteJson("/api/v1/bookings/{$reference}")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');
        $this->assertSame(0, $this->teeTime->fresh()->booked_players);

        $this->api()->deleteJson("/api/v1/bookings/{$reference}")
            ->assertStatus(409)
            ->assertJsonPath('error', 'invalid_state');
        $this->assertSame(0, $this->teeTime->fresh()->booked_players);
    }
}
