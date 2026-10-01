# Room Booking API

A REST API for booking meeting rooms, built with Laravel and MySQL. Prevents overlapping bookings for the same room, protects every endpoint with a simple API key, and includes a standalone HTML graph for visually browsing and managing bookings.

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

### Create the database

```bash
mysql -u root -p -e "CREATE DATABASE room_booking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Then set `DB_USERNAME` and `DB_PASSWORD` in `.env` to match your local setup.

### Set your API key

Pick any random string and add it to `.env`:

```env
API_KEY=room-booking-secret-7f3a9c2b
```

### Run migrations, seed, and start the server

```bash
php artisan migrate:fresh --seed
php artisan serve
```

API base URL: `http://127.0.0.1:8000/api`

## Authentication

Every API endpoint requires an API key, passed as a query parameter:

```
?key=your-key-here
```

Requests with no key, or the wrong key, get `401 Unauthorized`:

```json
{ "message": "Unauthorized. Missing or invalid API key." }
```

**Example directly in a browser:**
```
http://127.0.0.1:8000/api/rooms?key=room-booking-secret-7f3a9c2b
```

## Graph

A standalone HTML graph (`public/graph.html`) is included for visually browsing and managing bookings — a day-by-day timeline per room, with a side panel to add, edit, and delete bookings.

### Setup

The graph reads its API base URL and key from a small config file, kept separate so your real key never ends up in Git:

1. Copy `public/config.example.js` to `public/config.js`
2. Fill in your real values:
   ```js
   const API_CONFIG = {
     base: "http://127.0.0.1:8000/api",
     key: "room-booking-secret-7f3a9c2b"
   };
   ```
3. `config.js` is already listed in `.gitignore` — never commit it. Only `config.example.js` (with a blank key) is committed.

### Usage

With `php artisan serve` running, open:
```
http://127.0.0.1:8000/graph.html
```

- Use the date picker (◂ ▸ Today) to browse different days.
- Each room is a column; each booking is a block positioned by its start/end time.
- Click a block to edit or delete that booking.
- Use the side panel to add a new booking — the API's overlap rule is enforced the same as any other client.

## Endpoints

All requests below also require the `X-API-KEY` header (or `?key=` parameter) shown above — omitted from the examples below for brevity.

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

## Quick test commands

```bash
# List rooms
curl -H "Accept: application/json" -H "X-API-KEY: room-booking-secret-7f3a9c2b" \
  http://127.0.0.1:8000/api/rooms

# Create a booking
curl -X POST -H "Content-Type: application/json" -H "Accept: application/json" \
  -H "X-API-KEY: room-booking-secret-7f3a9c2b" \
  -d '{"room_id":1,"title":"Dev Meeting","booked_by":"Toms","starts_at":"2026-10-05 10:00:00","ends_at":"2026-10-05 11:00:00"}' \
  http://127.0.0.1:8000/api/bookings

# Update a booking
curl -X PUT -H "Content-Type: application/json" -H "Accept: application/json" \
  -H "X-API-KEY: room-booking-secret-7f3a9c2b" \
  -d '{"room_id":1,"title":"Dev Meeting (moved)","booked_by":"Toms","starts_at":"2026-10-05 11:00:00","ends_at":"2026-10-05 12:00:00"}' \
  http://127.0.0.1:8000/api/bookings/1

# Delete a booking
curl -X DELETE -H "Accept: application/json" -H "X-API-KEY: room-booking-secret-7f3a9c2b" \
  http://127.0.0.1:8000/api/bookings/1
```

## Decisions and assumptions

- **Overlap rule:** a new booking is rejected if `existing.starts_at < new.ends_at AND existing.ends_at > new.starts_at`. This allows back-to-back bookings (e.g. 09:00–10:00 followed immediately by 10:00–11:00).
- **Overlap and inactive-room errors** return HTTP 422 with a JSON `message` field.
- **Bookings cannot be created for inactive rooms.**
- **Booking creation runs inside a database transaction** with a row lock (`lockForUpdate`) on the room, to prevent two simultaneous requests from both passing the overlap check.
- **Timezone:** `APP_TIMEZONE` is set to `Europe/Riga`; the `current` endpoint compares against `now()` in this timezone.
- **Time formatting:** `schedule` and `current` return times as `HH:MM` (same-day context). `upcoming` returns full `YYYY-MM-DD HH:MM` since results can span multiple days.
- **API routes are JSON-only:** all API errors (validation, 404, 422) return JSON, enforced via `shouldRenderJsonWhen` in `bootstrap/app.php`.
- **Authentication:** a single shared API key, checked in `ApiKeyMiddleware`, applied to every route in `routes/api.php`. The key itself lives only in `.env` (server) and `config.js` (graph, gitignored) — never committed to Git.

## Seeding

```bash
php artisan migrate:fresh --seed
```

Creates 5+ rooms and 15–20 bookings with hand-picked, non-overlapping times, including one active "right now" for testing the `current` endpoint.

## Project structure (key files)

```
app/Http/Controllers/Api/RoomController.php
app/Http/Controllers/Api/BookingController.php
app/Http/Middleware/ApiKeyMiddleware.php
app/Http/Requests/StoreRoomRequest.php
app/Http/Requests/StoreBookingRequest.php
app/Models/Room.php
app/Models/Booking.php
database/migrations/
database/seeders/RoomSeeder.php
database/seeders/BookingSeeder.php
routes/api.php
public/graph.html
public/styles.css
public/config.example.js   (committed — blank key)
public/config.js           (gitignored — your real key)
```

