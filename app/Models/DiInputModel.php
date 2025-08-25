<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class DiInputModel extends Model
{
    use HasFactory;

    protected $table = 'di_input';
    protected $primaryKey = 'di_no';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'di_no',
        'gate',
        'po_number',
        'supplier_part_number',
        'supplier_part_number_desc',
        'baan_pn',
        'visteon_pn',
        'qty',
        'di_type',
        'di_status',
        'di_received_time',
        'di_received_date_string',
        'created_at',
        'updated_at',
    ];

    public $timestamps = true;

    protected $casts = [
        'di_received_date_string' => 'date:Y-m-d',
    ];

    // Relasi balik ke DS
    public function ds()
    {
        return $this->belongsTo(DsInput::class, 'supplier_part_number', 'supplier_part_number');
    }

    // Optional helper format tanggal
    public function getDiReceivedDateStringAttribute($value)
    {
        return $value ? Carbon::parse($value)->format('Y-m-d') : null;
    }

    
}
