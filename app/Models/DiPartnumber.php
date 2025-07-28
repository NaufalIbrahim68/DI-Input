<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiPartnumber extends Model
{
    protected $table = 'di_partnumber';

    protected $fillable = [
        'supplier_pn',
        'baan_pn',
        'visteon_pn',
    ];

    public $timestamps = true; // kalau tabel kamu punya created_at & updated_at
}
