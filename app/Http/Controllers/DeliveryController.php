<?php

namespace App\Http\Controllers;

use App\Models\DiInputModel;
use App\Models\DiPartnumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\ToArray;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class DeliveryController extends Controller
{
    // Constants untuk magic numbers
    const HEADER_ROWS_TO_SKIP = 5;
    const MAX_EXECUTION_TIME = 600; // 10 menit
    const MEMORY_LIMIT = '1024M'; // 1GB
    const CHUNK_SIZE = 1000; // Process data in chunks

    public function index()
    {
        $data =  DiInputModel::all();
           
        return view('DI_Input.index', [
            'data' => $data
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:51200' // Max 50MB
        ]);

        // Set execution limits
        ini_set('max_execution_time', self::MAX_EXECUTION_TIME);
        ini_set('memory_limit', self::MEMORY_LIMIT);

        try {
            // Load Excel data using a simple array import
            $data = Excel::toArray(new SimpleArrayImport(), $request->file('file'));

            if (empty($data) || empty($data[0])) {
                return back()->with('error', '❌ File kosong atau tidak dapat dibaca.');
            }

            // Pre-load semua reference data untuk menghindari N+1 query
            $references = $this->loadReferences();
            Log::info("📚 Loaded " . $references->count() . " reference data");

            // Process data
            $result = $this->processExcelData($data[0], $references);

            // Return response with appropriate message
            return $this->buildResponse($result);
        } catch (\Exception $e) {
            Log::error("❌ Import failed: " . $e->getMessage());
            return back()->with('error', '❌ Gagal mengimpor file: ' . $e->getMessage());
        }
    }

    /**
     * Pre-load semua reference data dengan optimized key
     */
    private function loadReferences()
    {
        return DiPartnumber::select('supplier_pn','baan_pn','visteon_pn')
            ->whereNotNull('supplier_pn')
            ->where('supplier_pn', '!=', '')
            ->get()
            ->keyBy(function ($item) {
                return $this->normalizePartNumber($item->supplier_pn);
            });
    }

    /**
     * Process Excel data in chunks untuk memory efficiency
     */
    private function processExcelData(array $rows, $references)
    {
        $importedCount = 0;
        $failedCount = 0;
        $failedRows = [];
        $totalRows = count($rows);

        // Process in chunks
        $chunks = array_chunk($rows, self::CHUNK_SIZE, true);

        foreach ($chunks as $chunk) {
            foreach ($chunk as $index => $row) {
                // Skip header rows dan empty rows
                if ($index <= self::HEADER_ROWS_TO_SKIP || $this->isEmptyRow($row)) {
                    continue;
                }

                // Validate required field
                if (!$this->validateRequiredFields($row)) {
                    Log::warning("⚠️ Lewati baris $index karena field wajib kosong.");
                    $failedRows[] = $index + 1;
                    $failedCount++;
                    continue;
                }

                try {
                    $this->processRow($row, $references);
                    $importedCount++;

                    // Log progress setiap 100 rows
                    if ($importedCount % 100 === 0) {
                        Log::info("📊 Progress: $importedCount/$totalRows rows processed");
                    }
                } catch (\Exception $e) {
                    $failedCount++;
                    $failedRows[] = $index + 1;
                    Log::error("❌ Gagal proses baris $index: " . $e->getMessage());
                    continue;
                }
            }

            // Clear memory setelah setiap chunk
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        }

        return [
            'imported' => $importedCount,
            'failed' => $failedCount,
            'failed_rows' => $failedRows
        ];
    }

    /**
     * Process single row
     */
    private function processRow(array $row, $references)
    {
        // Normalisasi supplier part number
        $supplierPN = $this->normalizePartNumber($row[6] ?? '');

        // Cari reference data
        $reference = $references->get($supplierPN);

        if ($reference) {
            Log::debug("✅ Reference found for PN: $supplierPN");
        } else {
            Log::debug("⚠️ No reference found for PN: $supplierPN");
        }

        // Siapkan data untuk update/create
        $updateData = $this->prepareUpdateData($row, $reference);

        // Save to database
        DiInputModel::updateOrCreate(
            ['di_no' => $row[0]],
            $updateData
        );

        Log::debug("✅ DiInput berhasil disimpan: " . $row[0]);
    }

    /**
     * Prepare data array for database update
     */
    private function prepareUpdateData(array $row, $reference = null)
    {
        $updateData = [
            'gate' => $row[1] ?? null,
            'po_number' => $row[2] ?? null,
            'po_item' => $row[3] ?? null,
            'supplier_id' => $row[4] ?? null,
            'supplier_desc' => $row[5] ?? null,
            'supplier_part_number' => $row[6] ?? null,
            'supplier_part_number_desc' => $row[7] ?? null,
            'qty' => $this->parseQty($row[8] ?? 0),
            'uom' => $row[9] ?? null,
            'critical_part' => $row[10] ?? null,
            'flag_subcontracting' => $row[11] ?? null,
            'po_status' => $row[12] ?? null,
            'latest_gr_date_po' => $this->parseDate($row[13] ?? null),
            'di_type' => $row[14] ?? null,
            'di_status' => $row[15] ?? null,
            'di_received_date' => $this->parseDate($row[16] ?? null),
            'di_received_time' => $row[17] ?? null,
            'di_created_date' => $this->parseDate($row[18] ?? null),
            'di_created_time' => $row[19] ?? null,
            'di_no_original' => $row[20] ?? null,
            'di_no_split' => $row[21] ?? null,
            'dn_no' => $row[22] ?? null,
            'plant_id_dn' => $row[23] ?? null,
            'plant_desc_dn' => $row[24] ?? null,
            'supplier_id_dn' => $row[25] ?? null,
            'supplier_desc_dn' => $row[26] ?? null,
            'plant_supplier_dn' => $row[27] ?? null,
        ];

        // Tambahkan reference data jika ada
        if ($reference) {
            if (!empty($reference->baan_pn)) {
                $updateData['baan_pn'] = $reference->baan_pn;
            }
            if (!empty($reference->visteon_pn)) {
                $updateData['visteon_pn'] = $reference->visteon_pn;
            }
        }

        return $updateData;
    }

    /**
     * Normalize part number untuk consistent matching
     */
    private function normalizePartNumber($partNumber)
    {
        if (empty($partNumber)) {
            return '';
        }

        return strtolower(str_replace([' ', '-', '_'], '', trim($partNumber)));
    }

    /**
     * Check if row is empty
     */
    private function isEmptyRow(array $row)
    {
        return empty(array_filter($row, function ($value) {
            return !empty(trim($value));
        }));
    }

    /**
     * Validate required fields
     */
    private function validateRequiredFields(array $row)
    {
        // Check di_no (required field)
        return !empty($row[0]) && !empty(trim($row[0]));
    }

    /**
     * Build response message based on import results
     */
    private function buildResponse(array $result)
    {
        $importedCount = $result['imported'];
        $failedCount = $result['failed'];
        $failedRows = $result['failed_rows'];

        if ($importedCount > 0 && $failedCount === 0) {
            $message = "✅ Berhasil mengimpor $importedCount data!";
            $alertType = 'success';
        } elseif ($importedCount > 0 && $failedCount > 0) {
            $failedRowsStr = implode(', ', array_slice($failedRows, 0, 10)); // Show max 10 failed rows
            if (count($failedRows) > 10) {
                $failedRowsStr .= '...';
            }
            $message = "⚠️ Berhasil mengimpor $importedCount data. Tapi ada $failedCount baris gagal (baris: $failedRowsStr)";
            $alertType = 'warning';
        } elseif ($importedCount === 0 && $failedCount > 0) {
            $message = "❌ Gagal mengimpor file. $failedCount baris gagal diproses.";
            $alertType = 'error';
        } else {
            $message = "❌ File kosong atau tidak ada data yang bisa diproses.";
            $alertType = 'error';
        }

        return back()->with($alertType, $message);
    }

    /**
     * Parse quantity with better error handling
     */
    private function parseQty($qty)
    {
        if (empty($qty)) {
            return 0;
        }

        // Handle different number formats
        $cleaned = preg_replace('/[^\d.,]/', '', $qty);
        $cleaned = str_replace(',', '.', $cleaned);

        return is_numeric($cleaned) ? (int) floor((float) $cleaned) : 0;
    }

    /**
     * Parse date with comprehensive format support
     */
    private function parseDate($date)
    {
        try {
            if (empty($date)) {
                return null;
            }

            // Handle Excel serial date
            if (is_numeric($date)) {
                return Date::excelToDateTimeObject($date);
            }

            // Handle string dates
            if (is_string($date)) {
                return \Carbon\Carbon::parse($date);
            }

            return null;
        } catch (\Exception $e) {
            Log::warning("⚠️ Tanggal tidak valid: " . json_encode($date) . " - Error: " . $e->getMessage());
            return null;
        }
    }
}

/**
 * Simple import class for converting Excel to array
 */
class SimpleArrayImport implements ToArray
{
    public function array(array $array)
    {
        return $array;
    }
}