<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $fillable = [
        'type',
        'status',
        'source',
        'user_role',
        'email',
        'planning_time',
        'note',
        'number_of_experts',
        'name',
        'preferred_contact_method',
        'phone',
        'address',
        'preferred_date',
        'preferred_time',
        'calculation_input',
        'calculation_output',
    ];

    protected $casts = [
        'calculation_input' => 'array',
        'calculation_output' => 'array',
        'preferred_date' => 'date',
    ];
}
