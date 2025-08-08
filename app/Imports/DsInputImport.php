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
        $rowNumber = $index + 2;

        try {
            $gate = $row['gate'] ?? null;
            $supplierPartNumber = $row['supplier_part_number'] ?? null;
            $qty = (int) ($row['qty'] ?? 0);
            $diType = $row['di_type'] ?? null;
            $diStatus = $row['di_status'] ?? null;
            $diReceivedDate = $this->parseDate($row['di_received_date'] ?? null);
            $diReceivedTime = $row['di_received_time'] ?? null;

            if (!$diReceivedDate) {
                $this->failedRows[] = [
                    'row_number' => $rowNumber,
                    'error' => "📅 Tanggal tidak valid atau kosong."
                ];
                continue;
            }

            $isDuplicate = DB::table('ds_input')
                ->where('gate', $gate)
                ->where('supplier_part_number', $supplierPartNumber)
                ->whereDate('di_received_date_string', $diReceivedDate)
                ->where('di_received_time', $diReceivedTime)
                ->exists();

            if ($isDuplicate) {
                $this->failedRows[] = [
                    'row_number' => $rowNumber,
                    'error' => "⚠️ Duplikat data dengan Gate & Supplier Part Number pada tanggal yang sama."
                ];
                continue;
            }

            $dsNumber = $this->generateDsNumber();

            DB::table('ds_input')->insert([
                'ds_number' => $dsNumber,
                'gate' => $gate,
                'supplier_part_number' => $supplierPartNumber,
                'qty' => $qty,
                'di_type' => $diType,
                'di_status' => $diStatus,
                'di_received_date_string' => $diReceivedDate,
                'di_received_time' => $diReceivedTime,
                'created_at' => now(),
                'updated_at' => now(),
                'flag' => 0,
            ]);

            $this->successCount++;
        } catch (\Throwable $e) {
            $this->failedRows[] = [
                'row_number' => $rowNumber,
                'error' => "❌ Error sistem: " . $e->getMessage()
            ];
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
