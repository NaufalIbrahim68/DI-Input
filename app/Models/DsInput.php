<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DsInput extends Model
{
    protected $table = 'ds_input';
    protected $primaryKey = 'ds_number';
    public $incrementing = false; // karena ds_number bukan auto increment
    protected $keyType = 'string';

    protected $fillable = [
        'ds_number',
        'gate',
        'supplier_part_number',
        'qty',
        'di_type',
        'di_status',
        'di_received_time',
        'di_received_date_string',
        'flag',
        'status_delivery', // hanya status, bukan data DN
    ];

    // Relasi: satu DS bisa punya banyak DN
    public function dn()
    {
        return $this->hasMany(Dn_Input::class, 'ds_number', 'ds_number');
    }
}
