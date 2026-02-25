<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertySaleImport extends Model
{
    protected $table = 'property_sale_imports';
    protected $fillable = [
        'file_name',
        'status',
        'total_rows',
        'inserted_rows',
        'error_message',
        'started_at',
        'completed_at',
        'created_by',
    ];
}
