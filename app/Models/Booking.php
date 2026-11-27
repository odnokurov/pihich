<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    public const CONSUMING = ['booked', 'attended', 'missed'];

    protected $fillable = ['user_id', 'slot_id', 'membership_id', 'status', 'attended_at'];

    public function slot()
    {
        return $this->belongsTo(TrainingSlot::class, 'slot_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeConsuming($query)
    {
        return $query->whereIn('status', self::CONSUMING);
    }
}
