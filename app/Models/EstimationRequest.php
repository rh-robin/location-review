<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstimationRequest extends Model
{
    protected $fillable = [
        'estimation_type',
        'postcode',
        'address',
        'input',
        'output',
    ];

    protected $casts = [
        'input' => 'array',
        'output' => 'array',
    ];
}
