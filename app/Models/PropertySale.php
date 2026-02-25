<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertySale extends Model
{
    protected $table = "property_sales";

    protected $fillable = [
        'transaction_id',
        'price',
        'transfer_date',
        'year',
        'postcode',
        'postcode_district',
        'postcode_sector',
        'property_type',
        'new_build',
        'duration',
        'town',
        'district',
        'county',
        'ppd_category',
        'record_status',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'year' => 'integer',
        'price' => 'integer',
    ];
}
