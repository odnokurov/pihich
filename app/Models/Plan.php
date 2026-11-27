<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = ['name', 'kind', 'duration_months', 'visits_limit', 'price_kopecks', 'is_active'];
}
