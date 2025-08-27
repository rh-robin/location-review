<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $table = 'locations';
    protected $fillable = [
        'name', 'latitude', 'longitude', 'status'
    ];

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function userLocations()
    {
        return $this->hasMany(UserLocation::class);
    }
}
