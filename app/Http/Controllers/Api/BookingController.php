<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Room;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    // POST /api/bookings
    public function store(StoreBookingRequest $request)
    {
        $data = $request->validated();

        return DB::transaction(function () use ($data) {
            // Lock the room row so two simultaneous requests can't both pass the check
            $room = Room::lockForUpdate()->findOrFail($data['room_id']);

            if (! $room->is_active) {
                return response()->json([
                    'message' => 'Room is not active.',
                ], 422);
            }

            // Two periods overlap when: existing.start < new.end AND existing.end > new.start
            $overlaps = Booking::where('room_id', $room->id)
                ->where('starts_at', '<', $data['ends_at'])
                ->where('ends_at', '>', $data['starts_at'])
                ->exists();

            if ($overlaps) {
                return response()->json([
                    'message' => 'Room is already booked for this period.',
                ], 422);
            }

            $booking = Booking::create($data);

            return response()->json($booking, 201);
        });
    }
}