<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $guarded = [];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function images()
    {
        return $this->hasMany(ReviewImage::class);
    }

    public function reactions()
    {
        return $this->hasMany(Reaction::class);
    }
    public function replies(){
        return $this->hasMany(Reply::class);
    }

}
