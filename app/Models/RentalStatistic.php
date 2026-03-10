<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RentalStatistic extends Model
{
    protected $table = 'rental_statistics';

    protected $fillable = [
        'area_code',
        'area_name',
        'region',
        'period_date',
        'year',
        'month',
        'rent_1_bed',
        'rent_2_bed',
        'rent_3_bed',
        'rent_4plus_bed',
        'rent_detached',
        'rent_semidetached',
        'rent_terraced',
        'rent_flat',
    ];

    protected $casts = [
        'period_date' => 'date',
        'year' => 'integer',
        'month' => 'integer',
        'rent_1_bed' => 'integer',
        'rent_2_bed' => 'integer',
        'rent_3_bed' => 'integer',
        'rent_4plus_bed' => 'integer',
        'rent_detached' => 'integer',
        'rent_semidetached' => 'integer',
        'rent_terraced' => 'integer',
        'rent_flat' => 'integer',
    ];


}
