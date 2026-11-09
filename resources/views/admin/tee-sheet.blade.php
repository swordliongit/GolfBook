@extends('layouts.admin')

@section('title', 'Tee sheet')

@section('content')
    <div class="page-head">
        <h1>Tee sheet</h1>
        <form method="GET" class="inline-form">
            <select name="course">
                @foreach ($courses as $c)
                    <option value="{{ $c->slug }}" @selected($course && $course->is($c))>{{ $c->name }}</option>
                @endforeach
            </select>
            <input type="date" name="date" value="{{ $date }}">
            <button class="btn">Show</button>
        </form>
    </div>

    <section class="card">
        @if ($teeTimes->isEmpty())
            <p class="muted">No tee times for this course and day.</p>
        @else
            <table class="tee-sheet">
                <thead><tr><th>Time</th><th>Occupancy</th><th>Bookings</th></tr></thead>
                <tbody>
                @foreach ($teeTimes as $teeTime)
                    <tr @class(['full' => $teeTime->freeSpots() === 0, 'past' => $teeTime->starts_at->isPast()])>
                        <td>{{ $teeTime->starts_at->format('H:i') }}</td>
                        <td>
                            <span class="slots">
                                @for ($i = 0; $i < $teeTime->capacity; $i++)
                                    <i @class(['taken' => $i < $teeTime->booked_players])></i>
                                @endfor
                            </span>
                            {{ $teeTime->booked_players }}/{{ $teeTime->capacity }}
                        </td>
                        <td>
                            @foreach ($teeTime->rounds as $round)
                                <a href="{{ route('admin.bookings.show', $round->booking) }}">{{ $round->booking->reference }}</a>
                                <span class="muted">{{ $round->booking->customer_name }} × {{ $round->players }}</span>@if (! $loop->last), @endif
                            @endforeach
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </section>
@endsection
