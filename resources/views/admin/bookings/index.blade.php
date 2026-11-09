@extends('layouts.admin')

@section('title', 'Bookings')

@section('content')
    <div class="page-head">
        <h1>Bookings</h1>
    </div>

    <form method="GET" class="card filters">
        <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Reference, name or e-mail">
        <select name="status">
            <option value="">All statuses</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
        <label>Arrival from <input type="date" name="from" value="{{ $filters['from'] ?? '' }}"></label>
        <label>to <input type="date" name="to" value="{{ $filters['to'] ?? '' }}"></label>
        <button class="btn primary">Filter</button>
        <a href="{{ route('admin.bookings.index') }}" class="btn">Reset</a>
    </form>

    <section class="card">
        <table>
            <thead>
            <tr><th>Reference</th><th>Status</th><th>Guest</th><th>Partner</th><th>Hotel</th><th>Stay</th><th class="num">Total</th></tr>
            </thead>
            <tbody>
            @forelse ($bookings as $booking)
                <tr>
                    <td><a href="{{ route('admin.bookings.show', $booking) }}">{{ $booking->reference }}</a></td>
                    <td><span class="badge {{ $booking->status->value }}">{{ $booking->status->label() }}</span></td>
                    <td>{{ $booking->customer_name }} <span class="muted">{{ $booking->customer_country }}</span></td>
                    <td>{{ $booking->partner?->name ?? 'Direct' }}</td>
                    <td>{{ $booking->hotel->name }}</td>
                    <td>{{ $booking->check_in->format('d.m') }}–{{ $booking->check_out->format('d.m.Y') }}</td>
                    <td class="num">€{{ number_format($booking->total_cents / 100, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="muted">No bookings match these filters.</td></tr>
            @endforelse
            </tbody>
        </table>

        {{ $bookings->links() }}
    </section>
@endsection
