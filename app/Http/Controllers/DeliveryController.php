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

        ini_set('max_execution_time', 600);
        ini_set('memory_limit', '1024M');

        try {
            $file = $request->file('file');
            Log::info("📁 Processing file: " . $file->getClientOriginalName());

            // Ambil semua sheet sebagai array
            $sheets = Excel::toArray(null, $file);
            if (empty($sheets) || empty($sheets[0])) {
                return back()->with('error', '❌ File kosong atau tidak bisa dibaca.');
            }

            $rows = $sheets[0]; // ambil sheet pertama
            $headerRows = 1;     // sesuaikan jika header lebih dari 1 baris

            $created = 0;
            $duplicates = 0;
            $failed = 0;
            $failedRows = [];

            // Ambil referensi part number
            $references = DiPartnumber::select('supplier_pn', 'baan_pn', 'visteon_pn')
                ->whereNotNull('supplier_pn')
                ->where('supplier_pn', '!=', '')
                ->get()
                ->keyBy(fn($item) => strtolower(str_replace([' ', '-', '_'], '', trim($item->supplier_pn))));

            $processedDiNumbers = [];

            foreach ($rows as $index => $row) {
                // skip header
                if ($index < $headerRows || empty(array_filter($row))) continue;

                $diNo = trim($row[0] ?? '');
                $gate = trim($row[1] ?? '');
                $supplierPN = trim($row[6] ?? '');

                // Validasi required
                if (empty($diNo) || strtolower($diNo) === 'di no' || empty($gate) || empty($supplierPN)) {
                    $failed++;
                    $failedRows[] = $index + 1;
                    Log::warning("❌ Row " . ($index + 1) . " gagal validasi: " . json_encode($row));
                    continue;
                }

                // Cek duplicate di file
                if (in_array($diNo, $processedDiNumbers)) {
                    $duplicates++;
                    Log::info("⚠️ Row " . ($index + 1) . " duplicate di file: $diNo");
                    continue;
                }

                // Cek duplicate di DB
                if (DiInputModel::where('di_no', $diNo)->exists()) {
                    $duplicates++;
                    Log::info("⚠️ Row " . ($index + 1) . " sudah ada di DB: $diNo");
                    continue;
                }

                // Ambil reference part number
                $normalizedPN = strtolower(str_replace([' ', '-', '_'], '', $supplierPN));
                $reference = $references->get($normalizedPN);

                // Persiapkan data
                $dataToInsert = [
                    'di_no' => $diNo,
                    'gate' => $gate,
                    'po_number' => $row[2] ?? null,
                    'supplier_part_number' => $supplierPN,
                    'supplier_part_number_desc' => $row[7] ?? null,
                    'qty' => is_numeric($row[8] ?? null) ? (int)$row[8] : 0,
                    'di_type' => $row[14] ?? null,
                    'di_received_date_string' => !empty($row[16]) ? \Carbon\Carbon::parse($row[16])->format('Y-m-d') : null,
                    'di_received_time' => !empty($row[17]) ? \Carbon\Carbon::parse($row[17])->format('H:i:s') : null,
                    'baan_pn' => $reference->baan_pn ?? null,
                    'visteon_pn' => $reference->visteon_pn ?? null,
                ];

                try {
                    DiInputModel::create($dataToInsert);
                    $created++;
                    $processedDiNumbers[] = $diNo;
                    Log::info("✅ Row " . ($index + 1) . " berhasil diimpor: $diNo");
                } catch (\Exception $e) {
                    $failed++;
                    $failedRows[] = $index + 1;
                    Log::error("❌ Row " . ($index + 1) . " gagal insert: " . $e->getMessage());
                }
            }

            // Build response message
            $messages = [];
            if ($created > 0) $messages[] = "✅ $created data berhasil diimpor";
            if ($duplicates > 0) $messages[] = "⚠️ $duplicates data duplicate";
            if ($failed > 0) $messages[] = "❌ $failed gagal (baris: " . implode(', ', array_slice($failedRows, 0, 10)) . ")";

            return back()->with('success', implode(' | ', $messages));
        } catch (\Exception $e) {
            Log::error("❌ Import gagal: " . $e->getMessage());
            return back()->with('error', '❌ Gagal mengimpor file: ' . $e->getMessage());
        }
    }

public function show($id)
{
    try {
        $diData = DiInputModel::findOrFail($id);
        return response()->json($diData);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Data tidak ditemukan',
            'id' => $id
        ], 404);
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

    private function processExcelData(array $rows, $references)
    {
        $createdCount = 0;
        $duplicateCount = 0;
        $failedCount = 0;
        $failedRows = [];
        $skippedRows = 0;
        $processedDiNumbers = []; // Track processed DI Numbers to prevent duplicates

        $totalRows = count($rows);
        Log::info("🔄 Starting to process $totalRows rows");

        // DEBUG: Log beberapa baris pertama untuk melihat struktur data
        for ($i = 0; $i < min(10, $totalRows); $i++) {
            $diNoSample = $this->cleanValue($rows[$i][0] ?? '');
            Log::info("📋 Row $i sample - DI No: '$diNoSample', Columns: " . count($rows[$i]));
        }

        foreach ($rows as $index => $row) {
            // Skip header rows dan empty rows
            if ($index <= self::HEADER_ROWS_TO_SKIP || $this->isEmptyRow($row)) {
                $skippedRows++;
                Log::debug("⏭️ Row " . ($index + 1) . ": Skipped (header/empty)");
                continue;
            }

            if (!$this->validateRequiredFields($row)) {
                $failedRows[] = $index + 1;
                $failedCount++;
                Log::warning("❌ Row " . ($index + 1) . ": Validation failed");
                continue;
            }

            $diNo = $this->cleanValue($row[0] ?? '');

            // DEBUG: Log setiap DI No yang diproses
            Log::debug("🔍 Row " . ($index + 1) . ": Processing DI No: '$diNo'");

            // Cek duplicate di memory processing (untuk file yang sama)
            if (in_array($diNo, $processedDiNumbers)) {
                $duplicateCount++;
                Log::warning("⚠️ Row " . ($index + 1) . ": Duplicate DI No in same file: $diNo");
                continue;
            }

            $status = $this->processDiInputRow($row, $references, $index);

            if ($status === 'created') {
                $createdCount++;
                $processedDiNumbers[] = $diNo; // Track berhasil diproses
                Log::info("✅ Row " . ($index + 1) . ": Created DI No: $diNo (Total created: $createdCount)");

                if ($createdCount % 25 == 0) {
                    Log::info("📊 Progress: $createdCount rows processed successfully");
                }
            } elseif ($status === 'duplicate') {
                $duplicateCount++;
                Log::info("⚠️ Row " . ($index + 1) . ": Already exists in DB: $diNo");
            } else {
                $failedRows[] = $index + 1;
                $failedCount++;
                Log::error("❌ Row " . ($index + 1) . ": Failed to process: $diNo");
            }
        }

        Log::info("✅ Processing completed - Total rows: $totalRows, Created: $createdCount, Duplicates: $duplicateCount, Failed: $failedCount, Skipped: $skippedRows");

        return [
            'created' => $createdCount,
            'duplicates' => $duplicateCount,
            'failed' => $failedCount,
            'failed_rows' => $failedRows,
            'skipped' => $skippedRows,
            'total_processed' => $totalRows - $skippedRows
        ];
    }

    private function processDiInputRow(array $row, $references, int $rowIndex)
    {
        $diNo = $this->cleanValue($row[0] ?? '');
        if (empty($diNo) || strtolower($diNo) === 'di no') {
            return 'failed';
        }

        // Cek existing di database
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
            $messages[] = "⚠️ {$result['duplicates']} data duplicate (sudah ada)";
        }

        if ($result['skipped'] > 0) {
            $messages[] = "⏭️ {$result['skipped']} baris dilewati (header/kosong)";
        }

        if ($result['failed'] > 0) {
            $failedRowsStr = implode(', ', array_slice($result['failed_rows'], 0, 10));
            if (count($result['failed_rows']) > 10) $failedRowsStr .= '...';
            $messages[] = "❌ {$result['failed']} gagal (baris: $failedRowsStr)";
        }

        // DEBUGGING INFO
        $totalExpected = $result['total_processed'] ?? 'unknown';
        $totalActual = $result['created'] + $result['duplicates'] + $result['failed'];
        $messages[] = "📊 Expected: $totalExpected, Actual processed: $totalActual";

        $fullMessage = implode(' | ', $messages);

        // ALERT jika ada discrepancy
        if ($result['created'] > ($result['total_processed'] ?? $result['created'])) {
            Log::alert("🚨 POTENTIAL DUPLICATE PROCESSING: Created ({$result['created']}) > Expected ({$result['total_processed']})");
            $fullMessage = "🚨 WARNING: Possible duplicate processing detected! " . $fullMessage;
        }

        if ($result['created'] > 0) {
            return back()->with('success', $fullMessage);
        } elseif ($result['duplicates'] > 0 && $result['failed'] === 0) {
            return back()->with('warning', $fullMessage);
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
