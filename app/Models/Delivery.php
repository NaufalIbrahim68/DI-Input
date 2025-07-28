<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    use HasFactory;

    protected $table = 'di_input'; // sesuaikan dengan nama tabel di DB

    protected $fillable = [
    'di_no', 'gate', 'po_number', 'po_item', 'supplier_id', 'supplier_desc',
    'supplier_part_number', 'supplier_part_number_desc', 'qty', 'uom',
    'critical_part', 'flag_subcontracting', 'po_status', 'latest_gr_date_po',
    'di_type', 'di_status', 'di_received_date', 'di_received_time',
    'di_created_date', 'di_created_time', 'di_no_original', 'di_no_split',
    'dn_no', 'plant_id_dn', 'plant_desc_dn', 'supplier_id_dn',
    'supplier_desc_dn', 'plant_supplier_dn',
];
  protected $dates = ['di_received_date', 'di_created_date',];
    public $timestamps = false;

   
}
