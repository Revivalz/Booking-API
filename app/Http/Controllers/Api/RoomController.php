<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoomRequest;
use Illuminate\Support\Facades\Validator;
use App\Models\Room;

class RoomController extends Controller
{
    // GET /api/rooms
    public function index()
    {
        $rooms = Room::where('is_active', true)
            ->get(['id', 'name', 'capacity', 'location']);

        return response()->json($rooms);
    }

    // GET /api/rooms/{room}  (missing room -> automatic 404)
    public function show(Room $room)
    {
        return response()->json($room);
    }

    // POST /api/rooms
    public function store(StoreRoomRequest $request)
    {
        $room = Room::create($request->validated());

        return response()->json($room->refresh(), 201);
    }
        // GET /api/rooms/{room}/schedule/{date}
        public function schedule(Room $room, string $date)
        {
            $validator = Validator::make(['date' => $date], [
                'date' => 'required|date_format:Y-m-d',
            ]);
    
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Invalid date. Use format YYYY-MM-DD.',
                    'errors' => $validator->errors(),
                ], 422);
            }
    
            $bookings = $room->bookings()
                ->whereDate('starts_at', $date)
                ->orderBy('starts_at')
                ->get()
                ->map(fn ($booking) => $booking->toTimeArray());
    
            return response()->json($bookings);
        }
    
        // GET /api/rooms/{room}/current
        public function current(Room $room)
        {
            $booking = $room->bookings()
                ->where('starts_at', '<=', now())
                ->where('ends_at', '>', now())
                ->first();
    
            if (! $booking) {
                return response()->json(['occupied' => false]);
            }
    
            return response()->json([
                'occupied' => true,
                'booking' => $booking->toTimeArray(),
            ]);
        }
    
        // GET /api/rooms/{room}/upcoming
        public function upcoming(Room $room)
        {
            $bookings = $room->bookings()
                ->where('starts_at', '>', now())
                ->orderBy('starts_at')
                ->limit(5)
                ->get()
                ->map(fn ($booking) => [
                    'title' => $booking->title,
                    'booked_by' => $booking->booked_by,
                    'starts_at' => $booking->starts_at->format('Y-m-d H:i'),
                    'ends_at' => $booking->ends_at->format('Y-m-d H:i'),
                ]);
    
            return response()->json($bookings);
        }
}