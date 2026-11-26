<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gym extends Model
{
    protected $fillable = ['name', 'address', 'description', 'image_path', 'is_active'];
}
