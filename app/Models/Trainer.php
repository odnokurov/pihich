<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trainer extends Model
{
    protected $fillable = ['user_id', 'specialty', 'bio', 'photo_path', 'is_active'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function gyms()
    {
        return $this->belongsToMany(Gym::class, 'trainer_gym')->withPivot('is_active')->withTimestamps();
    }

    public function activeGyms()
    {
        return $this->gyms()->wherePivot('is_active', true)->where('gyms.is_active', true);
    }
}
