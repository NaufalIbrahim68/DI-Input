<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dn_Input extends Model
{
    protected $table = 'dn_input';

    protected $fillable = [
        'ds_number', 'dn_number', 'qty'
    ];

    public function ds()
    {
        return $this->belongsTo(DsInput::class, 'ds_number', 'ds_number');
    }
}
