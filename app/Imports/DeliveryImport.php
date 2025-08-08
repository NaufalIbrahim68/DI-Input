<?php

namespace App\Imports;

use App\Models\DiInputModel;
use Illuminate\Support\Facades\DB;
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
        $originalSupplierPN = $row['supplier_part_number'] ?? '';
        $normalizedPN = $this->normalizeSupplierPN($originalSupplierPN);

        $reference = DB::table('di_partnumber')
            ->whereRaw("
                REPLACE(REPLACE(REPLACE(REPLACE(LOWER(supplier_pn), ' ', ''), '-', ''), '_', ''), '–', '') = ?
            ", [$normalizedPN])
            ->first();

        $baanPN = $reference->baan_pn ?? null;
        $visteonPN = $reference->visteon_pn ?? null;

        // Generate dan simpan ke ds_input
        $dsNumber = $this->generateDsNumber();

        DB::table('ds_input')->insert([
            'ds_number' => $dsNumber,
            'gate' => $row['gate'] ?? null,
            'supplier_part_number' => $originalSupplierPN,
            'qty' => $this->parseQty($row['qty'] ?? null),
            'di_type' => $row['di_type'] ?? null,
            'di_status' => $row['di_status'] ?? null,
            'di_received_date' => $this->parseDate($row['di_received_date'] ?? null),
            'di_received_time' => $row['di_received_time'] ?? null,
            'di_received_date_string' => optional($this->parseDate($row['di_received_date'] ?? null))->format('Y-m-d'),
            'created_at' => now(),
            'updated_at' => now(),
            'flag' => 0,
        ]);

        return new DiInputModel([
            'di_no' => $row['ds_number'] ?? null,
            'gate' => $row['gate'] ?? null,
            'po_number' => $row['po_number'] ?? null,
            'po_item' => $row['po_item'] ?? null,
            'supplier_id' => $row['supplier_id'] ?? null,
            'supplier_desc' => $row['supplier_desc'] ?? null,
            'supplier_part_number' => $originalSupplierPN,
            'supplier_part_number_desc' => $row['supplier_part_number_desc'] ?? null,
            'qty' => $this->parseQty($row['qty'] ?? null),
            'uom' => $row['uom'] ?? null,
            'critical_part' => $row['critical_part'] ?? null,
            'flag_subcontracting' => $row['flag_subcontracting'] ?? null,
            'po_status' => $row['po_status'] ?? null,
            'latest_gr_date_po' => $this->parseDate($row['latest_gr_date_po'] ?? null),
            'di_type' => $row['di_type'] ?? null,
            'di_status' => $row['di_status'] ?? null,
            'di_received_date' => $this->parseDate($row['di_received_date'] ?? null),
            'di_received_time' => $row['di_received_time'] ?? null,
            'di_created_date' => $this->parseDate($row['di_created_date'] ?? null),
            'di_created_time' => $row['di_created_time'] ?? null,
            'di_no_original' => $row['di_no_original'] ?? null,
            'di_no_split' => $row['di_no_split'] ?? null,
            'dn_no' => $row['dn_no'] ?? null,
            'plant_id_dn' => $row['plant_id_dn'] ?? null,
            'plant_desc_dn' => $row['plant_desc_dn'] ?? null,
            'supplier_id_dn' => $row['supplier_id_dn'] ?? null,
            'supplier_desc_dn' => $row['supplier_desc_dn'] ?? null,
            'plant_supplier_dn' => $row['plant_supplier_dn'] ?? null,
            'baan_pn' => strtoupper($baanPN),
            'visteon_pn' => strtoupper($visteonPN),
        ]);
    }

    private function parseQty($qty)
    {
        $cleaned = str_replace([',', ' '], '', trim($qty));
        return is_numeric($cleaned) ? (int)$cleaned : 0;
    }

    private function parseDate($date)
    {
        try {
            if (empty($date)) return null;
            if (is_numeric($date)) return Date::excelToDateTimeObject($date);
            return \Carbon\Carbon::parse($date);
        } catch (\Exception $e) {
            Log::warning("⚠️ Invalid date: " . json_encode($date));
            return null;
        }
    }

    private function normalizeSupplierPN($partNumber)
    {
        return strtolower(str_replace([' ', '-', '–', '_'], '', $partNumber));
    }

    private function generateDsNumber()
    {
        $today = now()->format('Ymd');
        $prefix = "DS-{$today}-";

        $last = DB::table('ds_input')
            ->whereDate('created_at', now()->toDateString())
            ->where('ds_number', 'like', "$prefix%")
            ->orderByDesc('ds_number')
            ->value('ds_number');

        $nextIncr = $last ? ((int)substr($last, -4)) + 1 : 1;
        return $prefix . str_pad($nextIncr, 4, '0', STR_PAD_LEFT);
    }
}
