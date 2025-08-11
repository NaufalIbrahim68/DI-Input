<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class DsInput extends Model
{
   

    protected $table = 'ds_input';
    protected $primaryKey = 'ds_number';
    public $incrementing = false; // karena bukan auto increment
    protected $keyType = 'string';

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'ds_number',
        'gate',
        'supplier_part_number',
        'qty',
        'di_type',
        'di_status',
        'di_received_date_string', // sesuaikan dengan kolom di tabel
        'di_received_time',
        'created_at',
        'updated_at',
        'flag'
    ];
}
