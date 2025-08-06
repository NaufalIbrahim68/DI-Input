<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DsInput extends Model
{
    protected $table = 'ds_input';

    public $timestamps = true;

    protected $fillable = [
        'ds_number',
        'gate',
        'supplier_part_number',
        'qty',
        'di_type',
        'di_status',
        'di_received_date',
        'di_received_time',
        'created_at',
        'updated_at',
        'flag'
    ];
}
