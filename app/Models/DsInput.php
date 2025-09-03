<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class DsInput extends Model
{
    use HasFactory;

    protected $table = 'ds_input';
    protected $primaryKey = 'ds_number';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'ds_number',
    'gate',
    'supplier_part_number',
    'qty',
    'di_type',
    'di_received_date_string',
    'di_received_time',
    'qty_prep',
    'flag_prep',
    'qty_agv',
    'flag_agv',
    'dn_number',
    'di_id',   
    'created_at',
    'updated_at'
    ];

    public $timestamps = true;

    // Relasi: satu DS bisa punya banyak DN
    public function dn()
    {
        return $this->hasMany(Dn_Input::class, 'ds_number', 'ds_number');
    }

    // Relasi: DS terkait ke banyak DI berdasarkan supplier_part_number
   public function di()
{
    return $this->belongsTo(DiInputModel::class, 'di_id', 'id');
}
    // Optional: helper format tanggal
    public function getFormattedReceivedDateAttribute()
    {
        return $this->di_received_date_string
            ? Carbon::parse($this->di_received_date_string)->format('Y-m-d')
            : null;
    }
}
