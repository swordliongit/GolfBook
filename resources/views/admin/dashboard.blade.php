@extends('layouts.admin')

@section('title', 'Today')

@section('content')
    <div class="page-head">
        <h1>Operations · {{ $day->format('l, d F Y') }}</h1>
        <form method="GET" class="inline-form">
            <input type="date" name="date" value="{{ $day->toDateString() }}">
            <button class="btn">Show</button>
        </form>
    </div>

    <section class="stats">
        <div class="stat"><span>{{ $stats['pending'] }}</span>Pending bookings</div>
        <div class="stat"><span>{{ $stats['arrivals_today'] }}</span>Arrivals on this day</div>
        <div class="stat"><span>{{ $stats['month_bookings'] }}</span>Bookings made this month</div>
        <div class="stat"><span>€{{ number_format($stats['month_revenue_cents'] / 100, 0, ',', '.') }}</span>Confirmed revenue this month</div>
    </section>

    <div class="grid-2">
        <section class="card">
            <h2>Tee times</h2>
            @forelse ($rounds as $courseName => $courseRounds)
                <h3>{{ $courseName }}</h3>
                <table>
                    <thead><tr><th>Time</th><th>Booking</th><th>Guest</th><th>Hotel</th><th>Players</th></tr></thead>
                    <tbody>
                    @foreach ($courseRounds as $round)
                        <tr>
                            <td>{{ $round->teeTime->starts_at->format('H:i') }}</td>
                            <td><a href="{{ route('admin.bookings.show', $round->booking) }}">{{ $round->booking->reference }}</a></td>
                            <td>{{ $round->booking->customer_name }}</td>
                            <td>{{ $round->booking->hotel->name }}</td>
                            <td>{{ $round->players }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @empty
                <p class="muted">No rounds booked for this day.</p>
            @endforelse
        </section>

        <section class="card">
            <h2>Airport transfers</h2>
            @if ($transfers->isEmpty())
                <p class="muted">No transfers for this day.</p>
            @else
                <table>
                    <thead><tr><th>Pick-up</th><th>Type</th><th>Flight</th><th>Guest</th><th>Hotel</th><th>Pax</th></tr></thead>
                    <tbody>
                    @foreach ($transfers as $transfer)
                        <tr>
                            <td>{{ $transfer->pickup_at->format('H:i') }}</td>
                            <td><span class="badge {{ $transfer->direction->value }}">{{ $transfer->direction->label() }}</span></td>
                            <td>{{ $transfer->flight_number ?? '—' }}</td>
                            <td><a href="{{ route('admin.bookings.show', $transfer->booking) }}">{{ $transfer->booking->customer_name }}</a></td>
                            <td>{{ $transfer->booking->hotel->name }}</td>
                            <td>{{ $transfer->passengers }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    </div>
@endsection
