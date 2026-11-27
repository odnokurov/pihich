<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatThread extends Model
{
    protected $fillable = ['user_id', 'trainer_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function trainer()
    {
        return $this->belongsTo(Trainer::class);
    }

    public function messages()
    {
        return $this->hasMany(ChatMessage::class, 'thread_id');
    }

    public function scopeForParticipant($query, User $user)
    {
        return $user->role === 'client'
            ? $query->where('user_id', $user->id)
            : $query->whereHas('trainer', fn ($trainer) => $trainer->where('user_id', $user->id));
    }
}
