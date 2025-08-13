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

    $dsNumber = $this->generateDsNumber();
    $diReceivedDateCarbon = $this->parseDate($row['di_received_date'] ?? null, true);
    $diReceivedDate = $diReceivedDateCarbon?->format('Y-m-d');

    // Simpan ke ds_input
    DB::table('ds_input')->insert([
        'ds_number' => $dsNumber,
        'gate' => $row['gate'] ?? null,
        'supplier_part_number' => $originalSupplierPN,
        'qty' => $this->parseQty($row['qty'] ?? null),
        'di_type' => $row['di_type'] ?? null,
        'di_status' => $row['di_status'] ?? null,
        'di_received_date' => $diReceivedDate,
        'di_received_time' => $this->parseTime($row['di_received_time'] ?? null),
        'baan_pn' => strtoupper($baanPN),
        'visteon_pn' => strtoupper($visteonPN),
        'created_at' => now(),
        'updated_at' => now(),
        'flag' => 0,
    ]);

    // Kalau mau tidak menyimpan ke di_input, return null saja
    return null;
}
    private function parseQty($qty)
    {
        $cleaned = str_replace([',', ' '], '', trim($qty));
        return is_numeric($cleaned) ? (int)$cleaned : 0;
    }

   private function parseDate($date, $returnCarbon = false)
{
    try {
        if (empty($date)) return null;

        if (is_numeric($date)) {
            $carbonDate = \Carbon\Carbon::instance(Date::excelToDateTimeObject($date));
        } else {
            $carbonDate = \Carbon\Carbon::createFromFormat('d-M-Y', strtoupper($date));
        }

        return $returnCarbon ? $carbonDate : $carbonDate->format('Y-m-d'); // default DB format
    } catch (\Exception $e) {
        Log::warning("⚠️ Invalid date: " . json_encode($date));
        return null;
    }
}

   private function parseTime($time)
{
    try {
        if (empty($time)) return null;

        if (is_numeric($time)) {
            // Excel numeric -> ambil jam saja
            return Date::excelToDateTimeObject($time)->format('H:i:s');
        }

        return date('H:i:s', strtotime($time));
    } catch (\Exception $e) {
        Log::warning("⚠️ Invalid time: " . json_encode($time));
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
