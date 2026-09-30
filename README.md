# Room Booking API

A REST API for booking meeting rooms, built with Laravel and MySQL. Prevents overlapping bookings for the same room and includes a simple HTML dashboard for viewing and managing bookings visually.

## Requirements

- PHP 8.2+
- Composer
- MySQL 8+

## Setup

```bash
git clone <repo-url>
cd room-booking-api
composer install
cp .env.example .env
php artisan key:generate
```

Create a MySQL database named `room_booking`:

```bash
mysql -u root -p -e "CREATE DATABASE room_booking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Then set `DB_USERNAME` and `DB_PASSWORD` in `.env` to match your local setup.

```bash
php artisan migrate:fresh --seed
php artisan serve
```

API base URL: `http://127.0.0.1:8000/api`

## Dashboard

A standalone HTML dashboard (`public/dashboard.html`) is included for visually browsing and managing bookings — a day-by-day timeline per room, with a panel to add, edit and delete bookings.

Open it at `http://127.0.0.1:8000/dashboard.html` while `php artisan serve` is running. It talks to the API directly from the browser (no build step needed) and expects the API base URL set near the top of its `<script>` tag to match your server address.

## Endpoints

### Rooms

| Method | URL | Description |
|---|---|---|
| GET | `/api/rooms` | List all active rooms |
| GET | `/api/rooms/{id}` | Get a single room (404 if it doesn't exist) |
| POST | `/api/rooms` | Create a new room |

**POST /api/rooms**
```json
// Request
{ "name": "Meeting Room B", "capacity": 6, "location": "3rd floor" }

// Response (201)
{ "id": 2, "name": "Meeting Room B", "capacity": 6, "location": "3rd floor", "is_active": true, ... }
```

### Room schedule

| Method | URL | Description |
|---|---|---|
| GET | `/api/rooms/{id}/schedule/{date}` | All bookings for a room on a given date (`YYYY-MM-DD`), sorted by start time |
| GET | `/api/rooms/{id}/current` | Whether the room is occupied right now |
| GET | `/api/rooms/{id}/upcoming` | The next 5 upcoming bookings for the room |

**GET /api/rooms/1/schedule/2026-10-05**
```json
[
  { "id": 4, "title": "Morning Meeting", "booked_by": "Anna", "starts_at": "09:00", "ends_at": "10:00" }
]
```

**GET /api/rooms/1/current**
```json
{ "occupied": true, "booking": { "title": "Dev Meeting", "booked_by": "Toms", "starts_at": "10:00", "ends_at": "11:00" } }
```

### Bookings

| Method | URL | Description |
|---|---|---|
| POST | `/api/bookings` | Create a new booking |
| PUT | `/api/bookings/{id}` | Update an existing booking |
| DELETE | `/api/bookings/{id}` | Delete a booking |

**POST /api/bookings**
```json
// Request
{
  "room_id": 1,
  "title": "Development Team Meeting",
  "booked_by": "Toms",
  "starts_at": "2026-10-05 10:00:00",
  "ends_at": "2026-10-05 11:00:00"
}

// Success: 201 + the created booking
// Overlap or validation failure: 422
{ "message": "Room is already booked for this period." }
```

`PUT /api/bookings/{id}` accepts the same body and applies the same overlap check, excluding the booking being edited. `DELETE /api/bookings/{id}` returns `204 No Content` on success.

## Decisions and assumptions

- **Overlap rule:** a new booking is rejected if `existing.starts_at < new.ends_at AND existing.ends_at > new.starts_at`. This allows back-to-back bookings (e.g. 09:00–10:00 followed immediately by 10:00–11:00).
- **Overlap and inactive-room errors** return HTTP 422 with a JSON `message` field.
- **Bookings cannot be created for inactive rooms.**
- **Booking creation runs inside a database transaction** with a row lock (`lockForUpdate`) on the room, to prevent two simultaneous requests from both passing the overlap check.
- **Timezone:** `APP_TIMEZONE` is set to `Europe/Riga`; the `current` endpoint compares against `now()` in this timezone.
- **Time formatting:** `schedule` and `current` return times as `HH:MM` (same-day context). `upcoming` returns full `YYYY-MM-DD HH:MM` since results can span multiple days.
- **API routes are JSON-only:** all API errors (validation, 404, 422) return JSON, enforced via `shouldRenderJsonWhen` in `bootstrap/app.php`.

## Seeding

```bash
php artisan migrate:fresh --seed
```

Creates 5+ rooms and 15–20 bookings with hand-picked, non-overlapping times, including one active "right now" for testing the `current` endpoint.

## Notes for reviewers

- No authentication is currently implemented — all endpoints are open. (Left as-is per the assignment's base scope; Sanctum auth is one of the listed optional extensions.)
- Request rate limiting has not yet been added — a reasonable next step before any public deployment would be Laravel's built-in `throttle` middleware.