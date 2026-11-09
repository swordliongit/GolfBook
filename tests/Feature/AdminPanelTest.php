<?php

namespace Tests\Feature;

use App\Data\BookingData;
use App\Models\Booking;
use App\Models\GolfCourse;
use App\Models\Hotel;
use App\Models\TeeTime;
use App\Models\User;
use App\Services\BookingService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private TeeTime $teeTime;

    private Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2027-01-10 12:00'));

        $hotel = Hotel::factory()->create();
        $this->teeTime = TeeTime::factory()->for(GolfCourse::factory())->create(['starts_at' => '2027-01-21 09:00:00']);

        $this->booking = app(BookingService::class)->create(new BookingData(
            hotelId: $hotel->id,
            checkIn: CarbonImmutable::parse('2027-01-20'),
            checkOut: CarbonImmutable::parse('2027-01-23'),
            guests: 2,
            customerName: 'Anna Schmidt',
            customerEmail: 'anna@example.de',
            customerCountry: 'DE',
            rounds: [$this->teeTime->id => 2],
        ));
    }

    public function test_guests_are_sent_to_the_login_page(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->get('/admin/bookings')->assertRedirect('/login');
    }

    public function test_staff_can_log_in(): void
    {
        $user = User::factory()->create(['password' => 'secret-pass']);

        $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => $user->email, 'password' => 'secret-pass'])->assertRedirect('/admin');
        $this->assertAuthenticatedAs($user);
    }

    public function test_pages_render(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/admin?date=2027-01-21')->assertOk()->assertSee($this->booking->reference);
        $this->get('/admin/tee-sheet?date=2027-01-21')->assertOk()->assertSee('09:00')->assertSee('Anna Schmidt');
        $this->get("/admin/bookings/{$this->booking->reference}")->assertOk()->assertSee('anna@example.de');
    }

    public function test_bookings_can_be_filtered(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/admin/bookings?q=Schmidt')->assertOk()->assertSee($this->booking->reference);
        $this->get('/admin/bookings?q=Nobody')->assertOk()->assertDontSee($this->booking->reference);
        $this->get('/admin/bookings?status=cancelled')->assertOk()->assertDontSee($this->booking->reference);
    }

    public function test_staff_can_confirm_and_cancel(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post("/admin/bookings/{$this->booking->reference}/confirm")->assertSessionHas('status');
        $this->assertSame('confirmed', $this->booking->fresh()->status->value);

        $this->post("/admin/bookings/{$this->booking->reference}/confirm")->assertSessionHas('error');

        $this->post("/admin/bookings/{$this->booking->reference}/cancel")->assertSessionHas('status');
        $this->assertSame('cancelled', $this->booking->fresh()->status->value);
        $this->assertSame(0, $this->teeTime->fresh()->booked_players);
    }
}
