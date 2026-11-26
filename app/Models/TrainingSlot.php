<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingSlot extends Model
{
    protected $fillable = ['trainer_id', 'gym_id', 'title', 'starts_at', 'ends_at', 'capacity', 'is_active'];

    protected $casts = ['starts_at' => 'immutable_datetime', 'ends_at' => 'immutable_datetime'];

    public function trainer()
    {
        return $this->belongsTo(Trainer::class);
    }

    public function gym()
    {
        return $this->belongsTo(Gym::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'slot_id');
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_active', true)
            ->whereHas('gym', fn ($gym) => $gym->where('is_active', true))
            ->whereHas('trainer.activeGyms', fn ($gym) => $gym->whereColumn('gyms.id', 'training_slots.gym_id'))
            ->whereHas('trainer', fn ($trainer) => $trainer->where('is_active', true));
    }
}
