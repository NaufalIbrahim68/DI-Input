<?php

namespace App\Imports;

use App\Models\DsInput; // Gunakan Model bukan DB langsung
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log;
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
             
              
                // Gunakan status dari mapping, jika tidak ada atau kosong gunakan default
                
                // Debug: log status yang diproses
                // Uncomment line di bawah untuk debug
                // \Log::info("Row " . ($index + 1) . " - Original status: '$statusValue', Final status: '$finalStatus'");
                
                // Validasi status - hanya terima 3 status yang valid
               

                // Parse tanggal dengan lebih baik
                $receivedDate = $this->parseDate($row['di_received_date'] ?? 
                                               $row['DI Received Date'] ?? 
                                               $row['di_received_date_string'] ?? null);

                // Cek duplikat
                $supplierPartNumber = $row['supplier_part_number'] ?? 
                                    $row['Supplier Part Number'] ?? '';
                                    
                $exists = DsInput::where('ds_number', $dsNumber)
                    ->where('supplier_part_number', $supplierPartNumber)
                    ->exists();

                if (!$exists) {
                    // Gunakan Model untuk konsistensi
                    DsInput::create([
                        'ds_number' => $dsNumber,
                        'gate' => $row['gate'] ?? $row['Gate'] ?? null,
                        'supplier_part_number' => $supplierPartNumber,
                        'qty' => isset($row['qty']) ? (int) $row['qty'] : 
                                (isset($row['Qty']) ? (int) $row['Qty'] : 0),
                        'di_type' => $row['di_type'] ?? 
                                    $row['DI Type'] ?? 
                                    $row['di type'] ?? null,
                        'di_received_date_string' => $receivedDate,
                        'di_received_time' => $this->parseTime($row['di_received_time'] ?? 
                                                              $row['DI Received Time'] ?? null),
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
              Log::error("Import error on row " . ($index + 1) . ": " . $e->getMessage());
            }
        }
    }

    private function parseDate($date)
    {
         if (empty($date)) return null;
    try {
        if (is_numeric($date)) {
            $excelDate = Carbon::instance(Date::excelToDateTimeObject($date));
            return $excelDate->format('Y-m-d');
        }
        return Carbon::parse($date)->format('Y-m-d');
    } catch (\Exception $e) {
        Log::error("Date parsing error: " . $e->getMessage());
        return null;
    }
    }

    private function parseTime($time)
    {
        if (empty($time)) return null;

        try {
            // Handle Excel time format (numeric - decimal representing time)
            if (is_numeric($time)) {
                // Excel time is stored as fraction of day
                if ($time < 1) {
                    $seconds = $time * 86400; // Convert to seconds
                    $hours = floor($seconds / 3600);
                    $minutes = floor(($seconds % 3600) / 60);
                    $secs = $seconds % 60;
                    return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
                } else {
                    // Full datetime
                    return Date::excelToDateTimeObject($time)->format('H:i:s');
                }
            }
            
            // Handle string time
            return Carbon::parse($time)->format('H:i:s');
            
        } catch (\Exception $e) {
         Log::error("Date parsing error for value '$date': " . $e->getMessage());
            return null;
        }
    }

    private function generateDsNumber()
    {
        $today = now()->format('ymd'); // Sesuaikan dengan format controller
        $prefix = "DS-{$today}-";

        $last = DsInput::where('ds_number', 'like', "$prefix%")
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