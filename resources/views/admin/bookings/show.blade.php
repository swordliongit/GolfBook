@extends('layouts.admin')

@section('title', $booking->reference)

@section('content')
    <div class="page-head">
        <h1>{{ $booking->reference }} <span class="badge {{ $booking->status->value }}">{{ $booking->status->label() }}</span></h1>

        <div class="actions">
            @if ($booking->status === \App\Enums\BookingStatus::Pending)
                <form method="POST" action="{{ route('admin.bookings.confirm', $booking) }}">
                    @csrf
                    <button class="btn primary">Confirm</button>
                </form>
            @endif
            @if ($booking->status->isActive())
                <form method="POST" action="{{ route('admin.bookings.cancel', $booking) }}"
                      onsubmit="return confirm('Cancel {{ $booking->reference }} and release its tee times?')">
                    @csrf
                    <button class="btn danger">Cancel booking</button>
                </form>
            @endif
        </div>
    </div>

    <div class="grid-2">
        <section class="card">
            <h2>Guest &amp; stay</h2>
            <dl>
                <dt>Guest</dt><dd>{{ $booking->customer_name }} ({{ $booking->customer_country }})</dd>
                <dt>E-mail</dt><dd>{{ $booking->customer_email }}</dd>
                <dt>Partner</dt><dd>{{ $booking->partner?->name ?? 'Direct' }}</dd>
                <dt>Hotel</dt><dd>{{ $booking->hotel->name }}</dd>
                <dt>Stay</dt><dd>{{ $booking->check_in->format('d.m.Y') }} – {{ $booking->check_out->format('d.m.Y') }} ({{ $booking->nights() }} nights)</dd>
                <dt>Guests</dt><dd>{{ $booking->guests }}</dd>
                @if ($booking->notes)
                    <dt>Notes</dt><dd>{{ $booking->notes }}</dd>
                @endif
                <dt>Created</dt><dd>{{ $booking->created_at->format('d.m.Y H:i') }}</dd>
                @if ($booking->confirmed_at)
                    <dt>Confirmed</dt><dd>{{ $booking->confirmed_at->format('d.m.Y H:i') }}</dd>
                @endif
                @if ($booking->cancelled_at)
                    <dt>Cancelled</dt><dd>{{ $booking->cancelled_at->format('d.m.Y H:i') }}</dd>
                @endif
            </dl>
        </section>

        <section class="card">
            <h2>Price</h2>
            <table>
                <tr><td>Hotel</td><td class="num">€{{ number_format($booking->hotel_total_cents / 100, 2, ',', '.') }}</td></tr>
                <tr><td>Green fees</td><td class="num">€{{ number_format($booking->golf_total_cents / 100, 2, ',', '.') }}</td></tr>
                <tr><td>Transfers</td><td class="num">€{{ number_format($booking->transfer_total_cents / 100, 2, ',', '.') }}</td></tr>
                <tr class="total"><td>Total</td><td class="num">€{{ number_format($booking->total_cents / 100, 2, ',', '.') }}</td></tr>
            </table>
        </section>
    </div>

    <div class="grid-2">
        <section class="card">
            <h2>Rounds</h2>
            <table>
                <thead><tr><th>Course</th><th>Tee time</th><th>Players</th><th class="num">Green fee</th></tr></thead>
                <tbody>
                @foreach ($booking->rounds->sortBy(fn ($r) => $r->teeTime->starts_at) as $round)
                    <tr>
                        <td>{{ $round->teeTime->golfCourse->name }}</td>
                        <td>{{ $round->teeTime->starts_at->format('d.m.Y H:i') }}</td>
                        <td>{{ $round->players }}</td>
                        <td class="num">€{{ number_format($round->green_fee_total_cents / 100, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </section>

        <section class="card">
            <h2>Transfers</h2>
            @if ($booking->transfers->isEmpty())
                <p class="muted">No transfers booked.</p>
            @else
                <table>
                    <thead><tr><th>Type</th><th>Pick-up</th><th>Flight</th><th>Pax</th></tr></thead>
                    <tbody>
                    @foreach ($booking->transfers as $transfer)
                        <tr>
                            <td><span class="badge {{ $transfer->direction->value }}">{{ $transfer->direction->label() }}</span></td>
                            <td>{{ $transfer->pickup_at->format('d.m.Y H:i') }}</td>
                            <td>{{ $transfer->flight_number ?? '—' }}</td>
                            <td>{{ $transfer->passengers }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    </div>
@endsection
