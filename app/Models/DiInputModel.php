<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiInputModel extends Model
{
    use HasFactory;

    protected $table = 'di_input';

    protected $fillable = [
        'di_no',
        'gate',
        'po_number',
        'po_item',
        'supplier_id',
        'supplier_desc',
        'supplier_part_number',
        'baan_pn',
        'visteon_pn',
        'supplier_part_number_desc',
        'qty',
        'uom',
        'critical_part',
        'flag_subcontracting',
        'po_status',
        'latest_gr_date_po',
        'di_type',
        'di_status',
        'di_received_date',
        'di_received_time',
        'di_created_date',
        'di_created_time',
        'di_no_original',
        'di_no_split',
        'dn_no',
        'plant_id_dn',
        'plant_desc_dn',
        'supplier_id_dn',
        'supplier_desc_dn',
        'plant_supplier_dn',
    ];

    protected $dates = ['di_received_date', 'di_created_date'];
    public $timestamps = false;

   protected $primaryKey = 'di_no';
public $incrementing = false;
protected $keyType = 'string';


    protected $casts = [
        'di_received_date' => 'datetime',
        'di_created_date' => 'datetime',
    ];

    /**
     * Handle case-insensitive attribute access
     * This will automatically handle both lowercase and uppercase column names
     */
    public function getAttribute($key)
    {
        // First try the original key
        if (array_key_exists($key, $this->attributes)) {
            return parent::getAttribute($key);
        }

        // If not found, try uppercase version
        $upperKey = strtoupper($key);
        if (array_key_exists($upperKey, $this->attributes)) {
            return $this->attributes[$upperKey];
        }

        // If still not found, try lowercase version
        $lowerKey = strtolower($key);
        if (array_key_exists($lowerKey, $this->attributes)) {
            return $this->attributes[$lowerKey];
        }

        // Default to parent behavior
        return parent::getAttribute($key);
    }

    /**
     * Handle case-insensitive attribute setting
     */
    public function setAttribute($key, $value)
    {
        // For baan_pn and visteon_pn, ensure consistent storage
        if (in_array(strtolower($key), ['baan_pn', 'visteon_pn'])) {
            // Store in lowercase to match fillable
            $key = strtolower($key);
        }

        return parent::setAttribute($key, $value);
    }

    public function show($id)
{
    $data = DiInputModel::find($id);

    if (!$data) {
        return response()->json(['message' => 'Data not found'], 404);
    }

    return response()->json($data);
}
}