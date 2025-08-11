<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as PhpDate;

class DsInputImport implements ToCollection, WithHeadingRow
{
    private int $successCount = 0;
    private array $failedRows = [];

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            try {
                // --- baca field dengan fleksibel (baik associative atau numeric)
                $gate = $this->getValue($row, ['gate', 'Gate', 0, 1]);
                $supplierPartNumber = $this->getValue($row, ['supplier_part_number', 'Supplier Part Number', 1, 2]);
                $qty = (int) ($this->getValue($row, ['qty', 'Qty', 2, 3]) ?? 0);
                $diType = $this->getValue($row, ['di_type', 'DI_Type', 'DI Type', 3, 4]);
                $diStatus = $this->getValue($row, ['di_status', 'DI_Status', 'DI Status', 4, 5]);
                $diReceivedRaw = $this->getValue($row, ['di_received_date', 'DI_Received_Date', 'di_received_date_string', 5, 6, 7]);
                $diReceivedDate = $this->parseDate($diReceivedRaw);
                $diReceivedTime = $this->getValue($row, ['di_received_time', 'DI_Received_Time', 6, 7]);

                // optional: jika semua key utama kosong -> skip
                if (empty($gate) && empty($supplierPartNumber)) {
                    // skip blank row
                    continue;
                }

                // --- cek duplikat / cari existing berdasarkan kombinasi unik
                $query = DB::table('ds_input')
                    ->where('gate', $gate)
                    ->where('supplier_part_number', $supplierPartNumber);

                if ($diReceivedDate !== null) {
                    $query->whereDate('di_received_date_string', $diReceivedDate);
                } else {
                    $query->whereNull('di_received_date_string');
                }

                if (!is_null($diReceivedTime)) {
                    $query->where('di_received_time', $diReceivedTime);
                } else {
                    $query->whereNull('di_received_time');
                }

                $existing = $query->first();

                // prepare payload common
                $payload = [
                    'gate' => $gate,
                    'supplier_part_number' => $supplierPartNumber,
                    'qty' => $qty,
                    'di_type' => $diType,
                    'di_status' => $diStatus,
                    'di_received_date_string' => $diReceivedDate,
                    'di_received_time' => $diReceivedTime,
                    'updated_at' => now(),
                    'flag' => 0,
                ];

                if ($existing) {
                    // update existing record (restore jika sebelumnya flag=1)
                    DB::table('ds_input')->where('ds_number', $existing->ds_number)->update($payload);
                } else {
                    // insert baru dengan ds_number generated
                    $payload['ds_number'] = $this->generateDsNumber();
                    $payload['created_at'] = now();
                    DB::table('ds_input')->insert($payload);
                }

                $this->successCount++;
            } catch (\Throwable $e) {
                Log::error("Import gagal baris {$rowNumber}: " . $e->getMessage(), [
                    'row' => is_array($row) ? $row : $row->toArray()
                ]);
                $this->failedRows[] = [
                    'row_number' => $rowNumber,
                    'error' => $e->getMessage(),
                ];
            }
        }
    }

    // helper: cari value di array (associative atau numeric) berdasar beberapa kemungkinan key
    private function getValue($row, array $keys)
    {
        // $row bisa berupa array atau Illuminate\Support\Collection
        $array = is_array($row) ? $row : $row->toArray();

        foreach ($keys as $k) {
            if (is_int($k)) {
                // numeric index
                if (array_key_exists($k, $array) && $array[$k] !== null && $array[$k] !== '') {
                    return $array[$k];
                }
            } else {
                // associative key (case-insensitive)
                foreach ($array as $ak => $av) {
                    if (is_string($ak) && strtolower($ak) === strtolower($k) && $av !== null && $av !== '') {
                        return $av;
                    }
                }
            }
        }
        return null;
    }

    private function generateDsNumber(): string
    {
        $today = now()->format('Ymd');
        $prefix = "DS-{$today}-";

        $last = DB::table('ds_input')
            ->whereDate('created_at', now()->toDateString())
            ->where('ds_number', 'like', "$prefix%")
            ->orderByDesc('ds_number')
            ->first();

        $next = 1;
        if ($last && isset($last->ds_number)) {
            $lastNum = (int) substr($last->ds_number, -4);
            $next = $lastNum + 1;
        }

        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    private function parseDate($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            // excel serial number?
            if (is_numeric($value)) {
                $dt = PhpDate::excelToDateTimeObject((float)$value);
                return Carbon::instance($dt)->format('Y-m-d');
            }

            // already a DateTime / Carbon?
            if ($value instanceof \DateTime) {
                return Carbon::instance($value)->format('Y-m-d');
            }

            // string parse
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            Log::warning("Format tanggal tidak dikenali: " . $value);
            return null;
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
}
