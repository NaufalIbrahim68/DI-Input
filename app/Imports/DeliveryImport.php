<?php

namespace App\Imports;

use App\Models\DiInputModel;
use App\Models\MasterData;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class DeliveryImport implements ToModel, WithHeadingRow, WithChunkReading
{
     
    public function chunkSize(): int
    {
        return 800;
        
    }
      public function startRow(): int
    {
        return 6; 
    }

    public function model(array $row)
    {
        
        return new DiInputModel([
            'di_no' => $row[0],
            'gate' => $row[1],
            'po_number' => $row[2],
            'po_item' => $row[3],
            'supplier_id' => $row[4],
            'supplier_desc' => $row[5],
            'supplier_part_number' => $row[6],
            'supplier_part_number_desc' => $row[7],
            'qty' => $this->parseQty($row[8]),  
            'uom' => $row[9],
            'critical_part' => $row[10],
            'flag_subcontracting' => $row[11],
            'po_status' => $row[12],
           'latest_gr_date_po' => $this->parseDate($row[13]),
            'di_type' => $row[14],
            'di_status' => $row[15],
           'di_received_date' => $this->parseDate($row[16]),
            'di_received_time' => $row[17],
           'di_created_date' => $this->parseDate($row[18]),
            'di_created_time' => $row[19],
            'di_no_original' => $row[20],
            'di_no_split' => $row[21],
            'dn_no' => $row[22],
            'plant_id_dn' => $row[23],
            'plant_desc_dn' => $row[24],
            'supplier_id_dn' => $row[25],
            'supplier_desc_dn' => $row[26],
            'plant_supplier_dn' => $row[27],
           

        ]);
    }

    /**
     * Membersihkan dan parsing qty
     */
    private function parseQty($qty)
    {
        $cleaned = preg_replace('/[^\d.]/', '', $qty); // hapus karakter selain angka & titik
        return is_numeric($cleaned) ? floor((float)$cleaned) : 0;
    }

    /**
     * Parsing tanggal dari Excel
     */
    private function parseDate($date)
    {
        try {
            if (is_numeric($date)) {
                return Date::excelToDateTimeObject($date);
            }
            return \Carbon\Carbon::parse($date);
        } catch (\Exception $e) {
            Log::warning("Tanggal tidak valid: " . $date);
            return null;
        }
    }
}
