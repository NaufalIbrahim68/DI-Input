<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;

class DsInputImport implements ToCollection, WithHeadingRow
{
    private int $successCount = 0;
    private array $failedRows = [];

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            try {
                $dsNumber = $this->generateDsNumber();

                DB::table('ds_input')->insert([
                    'ds_number' => $dsNumber,
                    'gate' => $row['gate'] ?? null,
                    'supplier_part_number' => $row['supplier_part_number'] ?? null,
                    'qty' => (int) ($row['qty'] ?? 0),
                    'di_type' => $row['di_type'] ?? null,
                    'di_status' => $row['di_status'] ?? null,
                    'di_received_date_string' => $this->parseDate($row['di_received_date'] ?? null),
                    'di_received_time' => $row['di_received_time'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'flag' => 0
                ]);

                $this->successCount++;
                Log::info("✅ Sukses insert ke ds_input: DS Number {$dsNumber}");
            } catch (\Throwable $e) {
                $rowNumber = $index + 2; // karena header ada di baris pertama

                $this->failedRows[] = [
                    'row_number' => $rowNumber,
                    'data' => $row->toArray(),
                    'error' => $e->getMessage(),
                ];

                Log::error("❌ Gagal insert ke ds_input pada baris Excel ke-{$rowNumber} | DS Number: {$dsNumber}");
                Log::error("📄 Data baris: " . json_encode($row));
                Log::error("💥 Error: " . $e->getMessage());
            }
        }
    }

    public function getSuccessCount(): int
    {
        return $this->successCount;
    }

    public function getFailedRows(): array
    {
        return $this->failedRows;
    }

    private function generateDsNumber()
    {
        $today = now()->format('Ymd');
        $prefix = "DS-{$today}-";

        $last = DB::table('ds_input')
            ->whereDate('created_at', now()->toDateString())
            ->where('ds_number', 'like', "$prefix%")
            ->orderByDesc('ds_number')
            ->first();

        $next = 1;
        if ($last) {
            $lastNum = (int) substr($last->ds_number, -4);
            $next = $lastNum + 1;
        }

        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    private function parseDate($value)
    {
        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            Log::warning("⚠️ Tanggal tidak valid: {$value}");
            return null;
        }
    }
}
