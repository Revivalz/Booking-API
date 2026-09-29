<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $rooms = Room::orderBy('id')->get();

        // helper: day offset from today, hour, minute
        $at = fn (int $days, int $h, int $m = 0) => Carbon::today()->addDays($days)->setTime($h, $m);

        $bookings = [
            // Room 1 (index 0)
            [0, 'Sprint Retrospective', 'Anna', $at(-1, 9),  $at(-1, 10)],
            [0, 'Live Standup',         'Toms', now()->subMinutes(20), now()->addMinutes(40)], // happening now
            [0, 'Client Call',          'Liga', $at(1, 10),  $at(1, 11)],
            [0, 'Morning Meeting',      'Anna', Carbon::create(2026, 10, 5, 9, 0),  Carbon::create(2026, 10, 5, 10, 0)],
            [0, 'Development Team Meeting', 'Toms', Carbon::create(2026, 10, 5, 10, 0), Carbon::create(2026, 10, 5, 11, 0)],

            // Room 2
            [1, 'Budget Review',    'Marta', $at(-1, 13), $at(-1, 14)],
            [1, 'Design Sync',      'Janis', $at(1, 9),   $at(1, 10, 30)],
            [1, 'Interview',        'Sanita', $at(1, 11), $at(1, 12)],
            [1, 'Product Demo',     'Anna',  $at(2, 14),  $at(2, 15)],

            // Room 3
            [2, 'All-hands',        'Director', $at(-2, 10), $at(-2, 12)],
            [2, 'Training Session', 'Liga',  $at(1, 13),  $at(1, 14)],
            [2, 'Workshop Part 1',  'Toms',  $at(3, 9),   $at(3, 10)],
            [2, 'Workshop Part 2',  'Toms',  $at(3, 10),  $at(3, 11)],

            // Room 4
            [3, 'One-on-one',       'Marta', $at(1, 15),  $at(1, 16)],
            [3, 'Code Review',      'Janis', $at(2, 9),   $at(2, 11)],
            [3, 'Planning',         'Sanita', $at(4, 12), $at(4, 13)],

            // Room 5
            [4, 'Brainstorm',       'Anna',  $at(1, 16),  $at(1, 17)],
            [4, 'Marketing Kickoff', 'Liga', $at(5, 10),  $at(5, 11)],
        ];

        foreach ($bookings as [$roomIndex, $title, $bookedBy, $start, $end]) {
            Booking::create([
                'room_id' => $rooms[$roomIndex]->id,
                'title' => $title,
                'booked_by' => $bookedBy,
                'starts_at' => $start,
                'ends_at' => $end,
            ]);
        }
    }
}