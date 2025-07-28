<?php

namespace App\Imports;

use App\Models\DiInputModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class DiInputImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        $importedCount = 0;
        $failedCount = 0;
        $failedRows = [];

        foreach ($rows as $index => $row) {
            if (empty($row['di_no'])) {
                continue;
            }

            try {
                $originalSupplierPN = $row['supplier_part_number'] ?? $row['Supplier Part Number'] ?? '';
                $normalizedPN = strtolower(str_replace([' ', '-', '–', '_'], '', $originalSupplierPN)); // Normalisasi PN

                Log::info("📋 DI: {$row['di_no']} | Supplier PN: $originalSupplierPN | Normalized: $normalizedPN");

                // Cari referensi berdasarkan normalized PN
                $reference = DB::table('di_partnumber')
                    ->whereRaw("
                        REPLACE(REPLACE(REPLACE(REPLACE(LOWER(supplier_pn), ' ', ''), '-', ''), '_', ''), '–', '') = ?
                    ", [$normalizedPN])
                    ->first();

                $baanPN = null;
                $visteonPN = null;

                if ($reference) {
                    $baanPN = $reference->baan_pn ?? null;
                    $visteonPN = $reference->visteon_pn ?? null;
                    Log::info("✅ Found Reference | BAAN_PN: $baanPN | VISTEON_PN: $visteonPN");
                } else {
                    Log::warning("❌ No Reference Found for: $originalSupplierPN");
                }

                $baseData = [
                    'gate' => $row['gate'],
                    'po_number' => $row['po_number'],
                    'po_item' => $row['po_item'],
                    'supplier_id' => $row['supplier_id'],
                    'supplier_desc' => $row['supplier_desc'],
                    'supplier_part_number' => $originalSupplierPN,
                    'supplier_part_number_desc' => $row['supplier_part_number_desc'],
                    'qty' => $this->parseQty($row['qty']),
                    'uom' => $row['uom'],
                    'critical_part' => $row['critical_part'],
                    'flag_subcontracting' => $row['flag_subcontracting'],
                    'po_status' => $row['po_status'],
                    'latest_gr_date_po' => $this->parseDate($row['latest_gr_date_po']),
                    'di_type' => $row['di_type'],
                    'di_status' => $row['di_status'],
                    'di_received_date' => $this->parseDate($row['di_received_date']),
                    'di_received_time' => $row['di_received_time'],
                    'di_created_date' => $this->parseDate($row['di_created_date']),
                    'di_created_time' => $row['di_created_time'],
                    'di_no_original' => $row['di_no_original'],
                    'di_no_split' => $row['di_no_split'],
                    'dn_no' => $row['dn_no'],
                    'plant_id_dn' => $row['plant_id_dn'],
                    'plant_desc_dn' => $row['plant_desc_dn'],
                    'supplier_id_dn' => $row['supplier_id_dn'],
                    'supplier_desc_dn' => $row['supplier_desc_dn'],
                    'plant_supplier_dn' => $row['plant_supplier_dn'],
                    'baan_pn' => $baanPN,
                    'visteon_pn' => $visteonPN,
                ];

                $di = DiInputModel::updateOrCreate(
                    ['di_no' => $row['di_no']],
                    $baseData
                );

                $importedCount++;
            } catch (\Exception $e) {
                $failedCount++;
                $failedRows[] = $index + 2;
                Log::error("❌ Error on row " . ($index + 2) . ": " . $e->getMessage());
            }
        }

        Log::info("✅ Total imported: $importedCount");
        if ($failedCount > 0) {
            Log::warning("❌ Total failed: $failedCount rows: " . implode(', ', $failedRows));
        }
    }

    private function parseQty($qty)
    {
        $cleaned = preg_replace('/[^\d.]/', '', $qty);
        return is_numeric($cleaned) ? floor((float)$cleaned) : 0;
    }

    private function parseDate($date)
    {
        try {
            if (empty($date)) return null;

            if (is_numeric($date)) {
                return Date::excelToDateTimeObject($date);
            }

            return \Carbon\Carbon::parse($date);
        } catch (\Exception $e) {
            Log::warning("⚠️ Invalid date: " . json_encode($date));
            return null;
        }
    }
}
