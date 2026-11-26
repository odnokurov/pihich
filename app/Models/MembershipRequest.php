<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MembershipRequest extends Model
{
    protected $fillable = [
        'user_id', 'gym_id', 'plan_id', 'duration_months', 'visits_per_month',
        'visits_limit', 'price_kopecks', 'status', 'start_on', 'end_on', 'rejection_reason', 'decided_by',
    ];

    public function gym()
    {
        return $this->belongsTo(Gym::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'membership_id');
    }

    public function scopeCovering($query, string $date)
    {
        return $query->where('status', 'approved')->where('start_on', '<=', $date)->where('end_on', '>', $date);
    }
}
