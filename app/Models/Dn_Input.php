<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dn_Input extends Model
{
    protected $table = 'dn_input';

    protected $fillable = [
        'ds_number',   // relasi ke DS
        'dn_number',   // nomor DN
        'qty',         // qty dari DS (referensi)
        'qty_dn',      // qty hasil input user
    ];

    // Relasi balik ke DS
    public function ds()
    {
        return $this->belongsTo(DsInput::class, 'ds_number', 'ds_number');
    }
}
