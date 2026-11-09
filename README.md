# GolfBook

Booking and operations system for a golf tour operator in Belek, Antalya.
Tour operators in the UK and Germany book golf holidays (hotel, tee times, airport transfers)
through a REST API; the operations team manages them in a web panel.

Built with **PHP 8.4, Laravel 13 and MySQL 8**.

## Features

**Partner REST API** (`/api/v1`)
- Hotels, golf courses and live tee time availability
- Price quotes without reserving anything
- Bookings with hotel stay, multiple rounds on different courses and airport transfers
- Cancellation that gives the tee time spots back
- API key authentication per tour operator, with per-partner rate limiting

**Operations panel** (`/admin`)
- Daily operations sheet: who tees off where, which transfers run, key numbers
- Tee sheet per course and day with occupancy
- Booking list with search and filters, booking detail, confirm and cancel

## Design decisions

| Problem | Solution |
|---|---|
| Two partners book the last spots of the same tee time at the same moment | The tee time rows are locked with `SELECT ... FOR UPDATE` inside the booking transaction. Rows are locked in id order to avoid deadlocks; the transaction is retried if MySQL still reports one. See `BookingService::create()`. |
| One full tee time in a multi-round booking | The whole booking runs in one transaction, so nothing is reserved unless every round fits. |
| Float rounding errors in prices | All money is stored as integer euro cents. |
| Prices change with the season | `PricingService` applies the season multiplier per night and per round, so a stay that crosses into high season is priced day by day. Seasons are loaded with one query per quote, not one per night. |
| Leaked API keys | Only the SHA-256 hash of a partner key is stored. The plain key is shown once when the partner is created. |
| Partners guessing other partners' booking references | Another partner's reference returns 404, not 403, so it cannot be confirmed that it exists. |
| Cancelling twice (double click, retried request) | The booking row is re-read under a lock; a second cancel is rejected and spots are released only once. |
| N+1 queries | `Model::shouldBeStrict()` makes lazy loading throw in development; every page eager-loads what it shows. |
| Business errors in the API | Domain exceptions (`TeeTimeUnavailableException`, `InvalidBookingException`, `BookingStateException`) render as JSON with a stable `error` code and the right HTTP status (409 / 422). |

## Structure

```
app/
  Data/              BookingData, TransferData, Quote (readonly value objects)
  Enums/             BookingStatus, TransferDirection
  Exceptions/        domain exceptions with JSON rendering
  Http/
    Controllers/Api/   partner API
    Controllers/Admin/ operations panel
    Middleware/        AuthenticatePartner (X-Api-Key)
    Requests/          validation
    Resources/         JSON output
  Models/
  Services/          BookingService, PricingService, TeeSheetGenerator
database/            migrations, factories, Belek demo seeder
tests/Feature/       API, pricing and admin panel tests
```

## Running it

Requirements: PHP 8.3+, Composer, and MySQL 8 (or SQLite for a quick look).

```bash
composer install
cp .env.example .env
php artisan key:generate

docker compose up -d          # MySQL 8 with the credentials from .env.example
php artisan migrate --seed    # Belek hotels, courses, seasons, 45 days of tee times, 40 demo bookings
php artisan serve
```

Without Docker, set `DB_CONNECTION=sqlite` in `.env` and run the same migrate command.

Operations panel: http://localhost:8000, login `ops@golfbook.test` / `password` (demo only).

Useful commands:

```bash
php artisan teesheet:generate --days=60        # open tee times (also scheduled daily at 02:00)
php artisan partner:create "Links Travel" GB   # register a tour operator, prints its API key once
php artisan test
```

## API examples

The seeder creates a demo partner with the key `demo-uk-fairway-key-0000000000000000000000`.

```bash
KEY=demo-uk-fairway-key-0000000000000000000000

# Free tee times for 4 players
curl -H "X-Api-Key: $KEY" "http://localhost:8000/api/v1/courses/lykia-links/tee-times?date=2026-11-20&players=4"

# Price a holiday
curl -H "X-Api-Key: $KEY" -H "Content-Type: application/json" -X POST http://localhost:8000/api/v1/quotes -d '{
  "hotel_id": 1, "check_in": "2026-11-19", "check_out": "2026-11-23", "guests": 2,
  "customer": {"name": "James Carter", "email": "james@example.co.uk", "country": "GB"},
  "rounds": [{"tee_time_id": 123, "players": 2}],
  "arrival": {"pickup_at": "2026-11-19 14:30", "flight_number": "TOM1234"}
}'

# Book it: same body, POST /api/v1/bookings  -> 201 with the booking reference
# Read it:   GET    /api/v1/bookings/{reference}
# Cancel it: DELETE /api/v1/bookings/{reference}
```

Error responses carry a machine-readable code:

```json
{ "message": "Lykia Links at 20.11.2026 09:00 has 1 free spot(s), 2 requested.", "error": "tee_time_unavailable" }
```

## Tests

```bash
php artisan test
```

Covers authentication, availability, overbooking protection, rollback of partially failing bookings,
validation, seasonal pricing, partner isolation, cancellation, and the admin pages.
