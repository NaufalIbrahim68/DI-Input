<?php

namespace App\Imports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Carbon\Carbon;

class DsInputImport implements ToCollection, WithHeadingRow
{
    private $successCount = 0;
    private $failedRows = [];

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            try {
                // Gunakan di_no jika ada, kalau tidak auto-generate
                $dsNumber = $row['di_no'] ?? $this->generateDsNumber();

                // Normalisasi status
                $status = strtolower(trim($row['di_status'] ?? ''));
                $statusMap = [
                    'created' => 'Created',
                    'used' => 'Used',
                    'received' => 'Received'
                ];
                $finalStatus = $statusMap[$status] ?? 'Created';

                // Cek duplikat
                $exists = DB::table('ds_input')
                    ->where('ds_number', $dsNumber)
                    ->where('supplier_part_number', $row['supplier_part_number'])
                    ->exists();

                if (!$exists) {
                    DB::table('ds_input')->insert([
                        'ds_number' => $dsNumber,
                        'gate' => $row['gate'] ?? null,
                        'supplier_part_number' => $row['supplier_part_number'] ?? null,
                        'qty' => isset($row['qty']) ? (int) $row['qty'] : 0,
                        'di_type' => $row['di_type'] ?? null,
                        'di_status' => $finalStatus,
                        'di_received_date_string' => $this->parseDate($row['di_received_date'] ?? null),
                        'di_received_time' => $this->parseTime($row['di_received_time'] ?? null),
                        'created_at' => now(),
                        'updated_at' => now(),
                        'flag' => 0
                    ]);
                    $this->successCount++;
                }
            } catch (\Exception $e) {
                $this->failedRows[] = [
                    'row' => $index + 1,
                    'error' => $e->getMessage(),
                    'data' => $row->toArray()
                ];
            }
        }
    }

    private function parseDate($date)
    {
        if (empty($date)) return null;

        try {
            if (is_numeric($date)) {
                return Carbon::instance(Date::excelToDateTimeObject($date))->format('Y-m-d');
            }
            return Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function parseTime($time)
    {
        if (empty($time)) return null;

        try {
            if (is_numeric($time)) {
                return Date::excelToDateTimeObject($time)->format('H:i:s');
            }
            return date('H:i:s', strtotime($time));
        } catch (\Exception $e) {
            return null;
        }
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

    public function getSuccessCount()
    {
        return $this->successCount;
    }

    public function getFailedRows()
    {
        return $this->failedRows;
    }
}
