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
                // Gunakan DI No jika ada, kalau tidak auto-generate
                $dsNumber = $row['di_no'] ?? 
                           $row['DI No'] ?? 
                           $row['di no'] ?? 
                           $this->generateDsNumber();

                // Debug: cek nama kolom yang tersedia
                // Uncomment line di bawah untuk debug
                // \Log::info('Available columns: ' . implode(', ', array_keys($row->toArray())));
                
                // Coba berbagai kemungkinan nama kolom untuk status
                $statusValue = $row['di_status'] ?? 
                              $row['DI Status'] ?? 
                              $row['di status'] ?? 
                              $row['status'] ?? '';
                
                $status = strtolower(trim($statusValue));
                $statusMap = [
                    'created' => 'Created',
                    'received' => 'Received', 
                    'used' => 'Used'
                ];
                
                // Gunakan status dari mapping, jika tidak ada atau kosong gunakan default
                $finalStatus = $statusMap[$status] ?? 'Created';
                
                // Debug: log status yang diproses
                // Uncomment line di bawah untuk debug
                // \Log::info("Row " . ($index + 1) . " - Original status: '$statusValue', Final status: '$finalStatus'");
                
                // Validasi status - hanya terima 3 status yang valid
                if (!in_array($finalStatus, ['Created', 'Received', 'Used'])) {
                    $finalStatus = 'Created';
                }

                // Cek duplikat
                $supplierPartNumber = $row['supplier_part_number'] ?? 
                                    $row['Supplier Part Number'] ?? '';
                                    
                $exists = DB::table('ds_input')
                    ->where('ds_number', $dsNumber)
                    ->where('supplier_part_number', $supplierPartNumber)
                    ->exists();

                if (!$exists) {
                    DB::table('ds_input')->insert([
                        'ds_number' => $dsNumber,
                        'gate' => $row['gate'] ?? $row['Gate'] ?? null,
                        'supplier_part_number' => $row['supplier_part_number'] ?? 
                                                 $row['Supplier Part Number'] ?? null,
                        'qty' => isset($row['qty']) ? (int) $row['qty'] : 
                                (isset($row['Qty']) ? (int) $row['Qty'] : 0),
                        'di_type' => $row['di_type'] ?? 
                                    $row['DI Type'] ?? 
                                    $row['di type'] ?? null,
                        'di_status' => $finalStatus,
                        'di_received_date_string' => $this->parseDate($row['di_received_date'] ?? 
                                                                    $row['DI Received Date'] ?? null),
                        'di_received_time' => $this->parseTime($row['di_received_time'] ?? 
                                                              $row['DI Received Time'] ?? null),
                        'created_at' => now(),
                        'updated_at' => now(),
                        'flag' => 0
                    ]);
                    $this->successCount++;
                } else {
                    // Log duplikat data untuk debugging
                    $this->failedRows[] = [
                        'row' => $index + 1,
                        'error' => 'Duplicate entry found',
                        'data' => $row->toArray()
                    ];
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