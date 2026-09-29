<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    protected $fillable = ['room_id', 'title', 'booked_by', 'starts_at', 'ends_at'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    // Used by schedule/current endpoints: time only, e.g. "09:00"
    public function toTimeArray(): array
    {
        return [
            'title' => $this->title,
            'booked_by' => $this->booked_by,
            'starts_at' => $this->starts_at->format('H:i'),
            'ends_at' => $this->ends_at->format('H:i'),
        ];
    }
}