<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class DiInputModel extends Model
{
    use HasFactory;

    protected $table = 'di_input';

    protected $primaryKey = 'di_no';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'di_no',
        'ds_number',
        'gate',
        'po_number',
        'supplier_part_number',
        'baan_pn',
        'visteon_pn',
        'supplier_part_number_desc',
        'qty',
        'di_type',
        'di_status',
        'di_received_date',
        'di_received_time',
        'di_received_date_string',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    public $timestamps = true;

    protected $casts = [
        'di_received_date' => 'date',
    ];
}
