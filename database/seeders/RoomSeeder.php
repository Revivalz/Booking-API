<?php

namespace Database\Seeders;

use App\Models\Room;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $rooms = [
            ['name' => 'Meeting Room A', 'capacity' => 4,  'location' => '2. stāvs', 'is_active' => true],
            ['name' => 'Meeting Room B', 'capacity' => 6,  'location' => '3. stāvs', 'is_active' => true],
            ['name' => 'Conference Hall', 'capacity' => 20, 'location' => '1. stāvs', 'is_active' => true],
            ['name' => 'Focus Room',      'capacity' => 2,  'location' => '2. stāvs', 'is_active' => true],
            ['name' => 'Creative Studio', 'capacity' => 8,  'location' => '4. stāvs', 'is_active' => true],
            ['name' => 'Old Storage Room', 'capacity' => 3, 'location' => 'Pagrabs',  'is_active' => false],
        ];

        foreach ($rooms as $room) {
            Room::create($room);
        }
    }
}