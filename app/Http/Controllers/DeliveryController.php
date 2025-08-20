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
use Carbon\Carbon;

class DeliveryController extends Controller
{
    const HEADER_ROWS_TO_SKIP = 5;
    const MAX_EXECUTION_TIME = 600;
    const MEMORY_LIMIT = '1024M';
    const CHUNK_SIZE = 1000;

    // Updated mapping berdasarkan header Excel baru (28 kolom)
    const EXCEL_COLUMN_MAPPING = [
        0 => 'di_no',                           // DI No
        1 => 'gate',                            // Gate
        2 => 'po_number',                       // PO Number
        3 => 'po_item',                         // PO Item
        4 => 'supplier_id',                     // Supplier ID
        5 => 'supplier_desc',                   // Supplier Desc
        6 => 'supplier_part_number',            // Supplier Part Number
        7 => 'supplier_part_number_desc',       // Supplier Part Number Desc
        8 => 'qty',                            // Qty
        9 => 'uom',                            // UOM
        10 => 'critical_part_flag',            // Critical Part Flag
        11 => 'subcontracting',                // Subcontracting
        12 => 'po_status',                     // PO Status
        13 => 'latest_gr_date',                // Latest GR Date
        14 => 'di_type',                       // PO DI Type
        15 => 'di_status',                     // DI Status
        16 => 'di_received_date_string',       // DI Received Date
        17 => 'di_received_time',              // DI Received Time
        18 => 'di_created_date',               // DI Created Date
        19 => 'di_created_time',               // DI Created Time
        20 => 'di_no_original',                // DI No Original
        21 => 'di_no_split',                   // DI No Split
        22 => 'dn_no',                         // DN No
        23 => 'plant_id_dn',                   // Plant ID (DN)
        24 => 'plant_desc_dn',                 // Plant Desc (DN)
        25 => 'supplier_id_dn',                // Supplier ID (DN)
        26 => 'supplier_desc_dn',              // Supplier Desc (DN)
        27 => 'plant_supplier_dn',             // Plant Supplier (DN)
    ];

    // Field yang akan disimpan ke database
    const DB_FIELDS = [
        'di_no',
        'gate',
        'po_number',
        'supplier_part_number',
        'supplier_part_number_desc',
        'qty',
        'di_type',
        'di_received_date_string',
        'di_received_time'
    ];

    public function index()
    {
        $data = DiInputModel::all();
        return view('DI_Input.index', ['data' => $data]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:51200'
        ]);

        ini_set('max_execution_time', self::MAX_EXECUTION_TIME);
        ini_set('memory_limit', self::MEMORY_LIMIT);

        try {
            // HANYA gunakan Excel::toArray, JANGAN gunakan Import class untuk menghindari double processing
            $data = Excel::toArray(new SimpleArrayImport(), $request->file('file'));
            if (empty($data) || empty($data[0])) {
                return back()->with('error', '❌ File kosong atau tidak dapat dibaca.');
            }

            Log::info("📁 Processing Excel file - Total sheets: " . count($data));
            Log::info("📊 First sheet rows: " . count($data[0]));

            $references = $this->loadReferences();
            $result = $this->processExcelData($data[0], $references);

            return $this->buildResponse($result);
        } catch (\Exception $e) {
            Log::error("❌ Import failed: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
            return back()->with('error', '❌ Gagal mengimpor file: ' . $e->getMessage());
        }
    }

    private function loadReferences()
    {
        return DiPartnumber::select('supplier_pn', 'baan_pn', 'visteon_pn')
            ->whereNotNull('supplier_pn')
            ->where('supplier_pn', '!=', '')
            ->get()
            ->keyBy(function ($item) {
                return $this->normalizePartNumber($item->supplier_pn);
            });
    }

   private function processExcelData(array $rows): array
{
    $createdCount   = 0;
    $duplicateCount = 0;
    $failedCount    = 0;
    $skippedRows    = 0;
    $batchData      = [];

    $totalRows = count($rows);

    // ✅ Ambil semua DI No existing sekali saja (lowercase biar konsisten)
    $existingDiNumbers = DiInputModel::pluck('di_no')->map(fn($v) => strtolower($v))->toArray();
    $existingDiNumbers = array_flip($existingDiNumbers); // biar lookup O(1)

    foreach ($rows as $index => $row) {
        // Skip baris header
        if ($index < self::HEADER_ROWS_TO_SKIP) {
            $skippedRows++;
            continue;
        }

        $diNo       = isset($row['di_no']) ? trim($row['di_no']) : null;
        $gate       = isset($row['gate']) ? trim($row['gate']) : null;
        $supplierPN = isset($row['supplier_part_number']) ? trim($row['supplier_part_number']) : null;

        // ✅ Validasi wajib
        if (empty($diNo) || empty($gate) || empty($supplierPN)) {
            $failedCount++;
            Log::warning("❌ Skipped row $index: Missing required fields", $row);
            continue;
        }

        // ✅ Cek duplicate (di memory & DB)
        if (isset($existingDiNumbers[strtolower($diNo)])) {
            $duplicateCount++;
            continue;
        }

        // Siapkan data
        $updateData = [
            'di_no'                  => $diNo,
            'gate'                   => $gate,
            'supplier_part_number'   => $supplierPN,
            'qty'                    => $this->parseQty($row['qty'] ?? null),
            'di_type'                => $row['di_type'] ?? null,
            'di_status'              => $row['di_status'] ?? null,
            'di_received_date'       => $this->parseDate($row['di_received_date'] ?? null),
            'di_received_time'       => $this->parseTime($row['di_received_time'] ?? null),
            'baan_pn'                => null,
            'visteon_pn'             => null,
            'created_at'             => now(),
            'updated_at'             => now(),
        ];

        // Lookup Part Number
        $part = DiPartNumber::where('supplier_part_number', $supplierPN)->first();
        if ($part) {
            $updateData['baan_pn']    = $part->baan_pn;
            $updateData['visteon_pn'] = $part->visteon_pn;
        } else {
            $failedCount++;
            Log::warning("⚠️ Supplier Part Number not found: $supplierPN (Row: $index)");
            continue;
        }

        // Tambahkan ke batch
        $batchData[] = $updateData;
        $existingDiNumbers[strtolower($diNo)] = true; // supaya di loop selanjutnya dianggap duplicate
    }

    // ✅ Sekali insert
    if (!empty($batchData)) {
        $created = DiInputModel::insertOrIgnore($batchData);
        $createdCount += $created;
    }

    // Hitung total processed lebih konsisten
    $totalProcessed = $createdCount + $duplicateCount + $failedCount;

    Log::info("📊 Import Summary: processed=$totalProcessed, created=$createdCount, duplicate=$duplicateCount, failed=$failedCount, skipped=$skippedRows");

    return [
        'total_rows'      => $totalRows,
        'total_processed' => $totalProcessed,
        'created'         => $createdCount,
        'duplicates'      => $duplicateCount,
        'failed'          => $failedCount,
        'skipped'         => $skippedRows,
    ];
}


    private function processDiInputRow(array $row, $references, int $rowIndex)
    {
        $diNo = $this->cleanValue($row[0] ?? '');
        if (empty($diNo) || strtolower($diNo) === 'di no') {
            return 'failed';
        }

        // Cek existing di databast
        $existing = DiInputModel::where('di_no', $diNo)->first();
        if ($existing) {
            Log::info("⚠️ Row " . ($rowIndex + 1) . ": DI No already exists in DB: $diNo");
            return 'duplicate';
        }

        $supplierPN = $this->normalizePartNumber($row[6] ?? ''); // Index 6 untuk Supplier Part Number
        $reference = $references->get($supplierPN);

        $updateData = $this->prepareDiInputData($row, $reference);
        $updateData['di_no'] = $diNo;

        try {
            DB::beginTransaction();

            // Insert ke di_input
            DiInputModel::create($updateData);

            // Generate DS (optional - bisa dipisah ke proses lain)
            // $this->generateDsFromDiRow($updateData);

            DB::commit();

            Log::debug("✅ Row " . ($rowIndex + 1) . ": Created DI No: $diNo");
            return 'created';
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("❌ Row " . ($rowIndex + 1) . ": Failed to create DI No: $diNo | " . $e->getMessage());
            return 'failed';
        }
    }

    private function prepareDiInputData(array $row, $reference = null)
    {
        $updateData = [];

        // Hanya ambil field yang diperlukan untuk database
        foreach (self::DB_FIELDS as $fieldName) {
            $excelIndex = array_search($fieldName, self::EXCEL_COLUMN_MAPPING);
            if ($excelIndex !== false) {
                $rawValue = $row[$excelIndex] ?? null;

                switch ($fieldName) {
                    case 'qty':
                        $updateData[$fieldName] = $this->parseQty($rawValue);
                        break;
                    case 'di_received_date_string':
                        $updateData[$fieldName] = $this->parseDate($rawValue);
                        break;
                    case 'di_received_time':
                        $updateData[$fieldName] = $this->parseTime($rawValue);
                        break;
                    default:
                        $updateData[$fieldName] = $this->cleanValue($rawValue);
                        break;
                }
            }
        }

        // Tambah reference data jika ada
        if ($reference) {
            $updateData['baan_pn'] = $reference->baan_pn ?? null;
            $updateData['visteon_pn'] = $reference->visteon_pn ?? null;
        }

        return $updateData;
    }

    private function cleanValue($value)
    {
        if (is_null($value)) return null;
        $cleaned = trim($value);
        return $cleaned === '' ? null : $cleaned;
    }

    private function normalizePartNumber($partNumber)
    {
        return strtolower(str_replace([' ', '-', '_'], '', trim($partNumber ?? '')));
    }

    private function isEmptyRow(array $row)
    {
        return empty(array_filter($row, fn($v) => !is_null($v) && trim($v) !== ''));
    }

    private function validateRequiredFields(array $row)
    {
        $diNo = $this->cleanValue($row[0] ?? '');
        $gate = $this->cleanValue($row[1] ?? '');
        $supplierPN = $this->cleanValue($row[6] ?? '');

        return !empty($diNo) &&
            strtolower($diNo) !== 'di no' &&
            !empty($gate) &&
            !empty($supplierPN);
    }

   private function buildResponse(array $result)
{
    $messages = [];

    if ($result['created'] > 0) {
        $messages[] = "✅ {$result['created']} data berhasil diimpor ke DI Input";
    }

    if ($result['duplicates'] > 0) {
        $messages[] = "❌ {$result['duplicates']} data gagal diimpor karena sudah ada (duplicate)";
    }

    if ($result['skipped'] > 0) {
        $messages[] = "⏭️ {$result['skipped']} baris dilewati (header/kosong)";
    }

    if ($result['failed'] > 0) {
        $failedRowsStr = implode(', ', array_slice($result['failed_rows'], 0, 10));
        if (count($result['failed_rows']) > 10) $failedRowsStr .= '...';
        $messages[] = "❌ {$result['failed']} gagal diproses (baris: $failedRowsStr)";
    }

    // DEBUG INFO
    $totalExpected = $result['total_processed'] ?? 'unknown';
    $totalActual = $result['created'] + $result['duplicates'] + $result['failed'];
    $messages[] = "📊 Expected: $totalExpected, Actual processed: $totalActual";

    $fullMessage = implode(' | ', $messages);

    // ALERT jika ada discrepancy
    if ($result['created'] > ($result['total_processed'] ?? $result['created'])) {
        Log::alert("🚨 POTENTIAL DUPLICATE PROCESSING: Created ({$result['created']}) > Expected ({$result['total_processed']})");
        $fullMessage = "🚨 WARNING: Possible duplicate processing detected! " . $fullMessage;
    }

    // ✅ logika notifikasi diperbaiki
    if ($result['created'] > 0) {
        return back()->with('success', $fullMessage);
    } else {
        return back()->with('error', "❌ Tidak ada data berhasil diimpor. $fullMessage");
    }
}

    private function parseQty($qty)
    {
        if (is_numeric($qty)) return (int) $qty;
        $cleaned = preg_replace('/[^\d]/', '', $qty ?? '');
        return is_numeric($cleaned) ? (int) $cleaned : 0;
    }

    private function parseDate($value)
    {
        if (empty($value)) return null;
        try {
            if (is_numeric($value)) {
                return Date::excelToDateTimeObject($value)->format('Y-m-d');
            }
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            Log::warning("❌ Date parsing error: " . json_encode($value));
            return null;
        }
    }

    private function parseTime($value)
    {
        if (empty($value)) return null;
        try {
            if (is_numeric($value)) {
                return Date::excelToDateTimeObject($value)->format('H:i:s');
            }
            return Carbon::parse($value)->format('H:i:s');
        } catch (\Exception $e) {
            Log::warning("❌ Time parsing error: " . json_encode($value));
            return null;
        }
    }
}

class SimpleArrayImport implements ToArray
{
    public function array(array $array)
    {
        return $array;
    }
}
