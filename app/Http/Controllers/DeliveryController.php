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
use App\Imports\DsInputImport;


class DeliveryController extends Controller
{
    const HEADER_ROWS_TO_SKIP = 5;
    const MAX_EXECUTION_TIME = 600;
    const MEMORY_LIMIT = '1024M';
    const CHUNK_SIZE = 1000;

    // Define expected column structures for each table
    const DI_INPUT_COLUMNS = [
        'di_no', 'gate', 'po_number', 'supplier_part_number', 
        'supplier_part_number_desc', 'qty', 'di_type', 
        'di_received_date_string', 'di_received_time'
    ];

    const DS_INPUT_COLUMNS = [
        'ds_number', 'gate', 'supplier_part_number', 'qty', 
        'di_type', 'di_status', 'di_received_date_string', 'di_received_time'
    ];

    public function index()
    {
        $data = DiInputModel::all();

        return view('DI_Input.index', [
            'data' => $data
        ]);
    }

    public function show($id)
    {
        $data = DB::table('di_input')->where('id', $id)->first();

        if (!$data) {
            return response()->json(['message' => 'Data not found'], 404);
        }

        return response()->json($data);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:51200'
        ]);

        ini_set('max_execution_time', self::MAX_EXECUTION_TIME);
        ini_set('memory_limit', self::MEMORY_LIMIT);

        try {
            $data = Excel::toArray(new SimpleArrayImport(), $request->file('file'));

            if (empty($data) || empty($data[0])) {
                return back()->with('error', '❌ File kosong atau tidak dapat dibaca.');
            }

            // Detect which table structure the Excel data matches
            $targetTable = $this->detectTableStructure($data[0]);
            
            if ($targetTable === 'unknown') {
                return back()->with('error', '❌ Struktur file Excel tidak sesuai dengan format DI Input atau DS Input.');
            }

            Log::info("📋 Detected table structure: $targetTable");

            $references = $this->loadReferences();
            Log::info("📚 Loaded " . $references->count() . " reference data");

            $result = $this->processExcelData($data[0], $references, $targetTable);

            return $this->buildResponse($result, $targetTable);
        } catch (\Exception $e) {
            Log::error("❌ Import failed: " . $e->getMessage());
            return back()->with('error', '❌ Gagal mengimpor file: ' . $e->getMessage());
        }
    }

    private function detectTableStructure(array $rows)
    {
        // Skip header rows and find the first data row with headers
        $headerRow = null;
        for ($i = 0; $i <= self::HEADER_ROWS_TO_SKIP && $i < count($rows); $i++) {
            if (!$this->isEmptyRow($rows[$i])) {
                $headerRow = $rows[$i];
                break;
            }
        }

        if (!$headerRow) {
            return 'unknown';
        }

        // Normalize headers for comparison
        $normalizedHeaders = array_map(function($header) {
            return strtolower(trim(str_replace([' ', '_', '-'], '', $header)));
        }, $headerRow);

        // Check for DI Input structure indicators
        $diIndicators = ['dino', 'ponumber', 'supplierpartnumberdesc'];
        $dsIndicators = ['dsnumber', 'distatus'];

        $diMatches = 0;
        $dsMatches = 0;

        foreach ($normalizedHeaders as $header) {
            if (in_array($header, $diIndicators)) {
                $diMatches++;
            }
            if (in_array($header, $dsIndicators)) {
                $dsMatches++;
            }
        }

        // Additional logic: check specific column patterns
        // If we find 'DI No' or 'PO Number' it's likely DI Input
        // If we find 'DS Number' or 'DI Status' it's likely DS Input
        
        if ($dsMatches > 0 || in_array('dsnumber', $normalizedHeaders)) {
            return 'ds_input';
        } elseif ($diMatches > 0 || in_array('dino', $normalizedHeaders) || in_array('ponumber', $normalizedHeaders)) {
            return 'di_input';
        }

        // Fallback: analyze data content in first few rows
        return $this->detectByDataContent($rows);
    }

    private function detectByDataContent(array $rows)
    {
        $sampleRows = array_slice($rows, self::HEADER_ROWS_TO_SKIP + 1, 5);
        
        foreach ($sampleRows as $row) {
            if ($this->isEmptyRow($row)) continue;
            
            // Check first column patterns
            $firstCol = trim($row[0] ?? '');
            
            // DS Number pattern: DS-YYYYMMDD-XXXX
            if (preg_match('/^DS-\d{8}-\d{4}$/', $firstCol)) {
                return 'ds_input';
            }
            
            // DI Number pattern (typically starts with DI or similar)
            if (preg_match('/^DI/i', $firstCol) || preg_match('/^\d+$/', $firstCol)) {
                return 'di_input';
            }
        }

        return 'unknown';
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

    private function processExcelData(array $rows, $references, string $targetTable)
    {
        $createdCount = 0;
        $failedCount = 0;
        $failedRows = [];

        $chunks = array_chunk($rows, self::CHUNK_SIZE, true);

        foreach ($chunks as $chunk) {
            foreach ($chunk as $index => $row) {
                if ($index <= self::HEADER_ROWS_TO_SKIP || $this->isEmptyRow($row)) {
                    continue;
                }

                if (!$this->validateRequiredFields($row, $targetTable)) {
                    Log::warning("⚠️ Lewati baris $index karena field wajib kosong.");
                    $failedRows[] = $index + 1;
                    $failedCount++;
                    continue;
                }

                try {
                    $status = $this->processRow($row, $references, $targetTable);

                    if ($status === 'created') {
                        $createdCount++;
                    } else {
                        $failedCount++;
                        $failedRows[] = $index + 1;
                    }

                    if (($createdCount + $failedCount) % 100 === 0) {
                        Log::info("📊 Progress: " . ($createdCount + $failedCount) . "/" . count($rows) . " rows processed");
                    }
                } catch (\Exception $e) {
                    $failedCount++;
                    $failedRows[] = $index + 1;
                    Log::error("❌ Gagal proses baris $index: " . $e->getMessage());
                }
            }

            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        }

        return [
            'created' => $createdCount,
            'failed' => $failedCount,
            'failed_rows' => $failedRows
        ];
    }

    private function processRow(array $row, $references, string $targetTable)
    {
        if ($targetTable === 'di_input') {
            return $this->processDiInputRow($row, $references);
        } elseif ($targetTable === 'ds_input') {
            return $this->processDsInputRow($row);
        }
        
        return 'failed';
    }

    private function processDiInputRow(array $row, $references)
    {
        $diNo = trim($row[0] ?? '');

        // Validasi DI No
        if (empty($diNo) || strtolower($diNo) === 'di no') {
            Log::warning("⚠️ DI No kosong atau tidak valid di baris Excel.");
            return 'failed';
        }

        $supplierPN = $this->normalizePartNumber($row[3] ?? '');
        $reference = $references->get($supplierPN);

        $updateData = $this->prepareDiInputData($row, $reference);
        $updateData['di_no'] = $diNo;

        try {
            $existing = DiInputModel::where('di_no', $diNo)->exists();
            if (!$existing) {
                DiInputModel::create($updateData);
                Log::debug("🆕 DiInput baru disimpan: $diNo");
                return 'created';
            } else {
                Log::info("⚠️ DI No $diNo sudah ada di di_input.");
                return 'failed';
            }
        } catch (\Exception $e) {
            Log::error("❌ Gagal insert ke di_input untuk DI No: $diNo | " . $e->getMessage());
            return 'failed';
        }
    }

    private function processDsInputRow(array $row)
    {
        try {
            $originalDsNumber = trim($row[0] ?? '');
            $gate = $row[1] ?? null;
            $supplierPartNumber = $row[2] ?? null;
            $diReceivedDate = !empty($row[6]) ? \Carbon\Carbon::parse($row[6])->format('Y-m-d') : null;

            // Selalu generate DS Number dengan format standar, abaikan yang dari Excel
            $dsNumber = $this->generateDsNumber();

            // Log original DS Number untuk tracking
            if (!empty($originalDsNumber) && $originalDsNumber !== $dsNumber) {
                Log::info("📝 Original DS Number '$originalDsNumber' diganti dengan '$dsNumber'");
            }

            // Cek duplikat berdasarkan kombinasi gate + supplier_part_number + date
            $isDuplicate = DB::table('ds_input')
                ->where('gate', $gate)
                ->where('supplier_part_number', $supplierPartNumber)
                ->whereDate('di_received_date_string', $diReceivedDate)
                ->exists();

            if ($isDuplicate) {
                Log::warning("⚠️ Duplikat DS terdeteksi berdasarkan gate-part-date: $gate - $supplierPartNumber - $diReceivedDate");
                return 'failed';
            }

            DB::table('ds_input')->insert([
                'ds_number' => $dsNumber,
                'gate' => $gate,
                'supplier_part_number' => $supplierPartNumber,
                'qty' => $this->parseQty($row[3] ?? 0),
                'di_type' => $row[4] ?? null,
                'di_status' => $row[5] ?? null,
                'di_received_date_string' => $diReceivedDate,
                'di_received_time' => $row[7] ?? null, 
                'created_at' => now(),
                'updated_at' => now(),
                'flag' => 0,
            ]);

            Log::info("🟢 Berhasil insert ke ds_input: $dsNumber (Original: $originalDsNumber)");
            return 'created';
        } catch (\Exception $e) {
            Log::error("❌ Gagal insert ke ds_input: " . $e->getMessage());
            return 'failed';
        }
    }

    private function prepareDiInputData(array $row, $reference = null)
    {
        $updateData = [
            'di_no' => $row[0] ?? null,
            'gate' => $row[1] ?? null,
            'po_number' => $row[2] ?? null,
            'supplier_part_number' => $row[3] ?? null,
            'supplier_part_number_desc' => $row[4] ?? null,
            'qty' => $this->parseQty($row[5] ?? 0),
            'di_type' => $row[6] ?? null,
            'di_received_date_string' => !empty($row[7]) ? \Carbon\Carbon::parse($row[7])->format('d-M-Y') : null,
            'di_received_time' => $row[7] ?? null,
        ];

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

    private function normalizePartNumber($partNumber)
    {
        return strtolower(str_replace([' ', '-', '_'], '', trim($partNumber)));
    }

    private function isEmptyRow(array $row)
    {
        return empty(array_filter($row, function ($value) {
            return !empty(trim($value));
        }));
    }

    private function validateRequiredFields(array $row, string $targetTable)
    {
        if ($targetTable === 'di_input') {
            $diNo = trim($row[0] ?? '');
            return !empty($diNo) && strtolower($diNo) !== 'di no';
        } elseif ($targetTable === 'ds_input') {
            // For DS Input, we can generate DS Number if empty, so check other required fields
            $gate = trim($row[1] ?? '');
            $supplierPartNumber = trim($row[2] ?? '');
            return !empty($gate) || !empty($supplierPartNumber);
        }
        
        return false;
    }

    private function buildResponse(array $result, string $targetTable)
    {
        $created = $result['created'];
        $failed = $result['failed'];
        $failedRows = $result['failed_rows'];

        $tableName = $targetTable === 'di_input' ? 'DI Input' : 'DS Input';
        $messageParts = [];

        if ($created > 0) {
            $messageParts[] = "✅ $created data berhasil diimpor ke $tableName";
        }

        if ($failed > 0) {
            $failedRowsStr = implode(', ', array_slice($failedRows, 0, 10));
            if (count($failedRows) > 10) {
                $failedRowsStr .= '...';
            }
            $messageParts[] = "❌ $failed gagal (baris: $failedRowsStr)";
        }

        $fullMessage = implode(' | ', $messageParts);

        if ($created > 0 && $failed === 0) {
            return back()->with('success', $fullMessage);
        } elseif ($created > 0 && $failed > 0) {
            return back()->with('warning', $fullMessage);
        } else {
            return back()->with('error', "❌ Tidak ada data berhasil diimpor ke $tableName.");
        }
    }

    private function parseQty($qty)
    {
        $cleaned = preg_replace('/[^\d.,]/', '', $qty);
        $cleaned = str_replace(',', '.', $cleaned);

        return is_numeric($cleaned) ? (int) floor((float) $cleaned) : 0;
    }

    private function generateDsNumber()
    {
        $today = now()->format('Ymd');
        $prefix = "DS-{$today}-";

        // Get the highest increment number for today (thread-safe)
        $last = DB::table('ds_input')
            ->whereDate('created_at', now()->toDateString())
            ->where('ds_number', 'like', "$prefix%")
            ->lockForUpdate() // Prevent race condition
            ->orderByDesc('ds_number')
            ->value('ds_number');

        if ($last) {
            // Extract the increment number from the last DS number
            $lastIncr = (int) substr($last, -4);
            $nextIncr = $lastIncr + 1;
        } else {
            $nextIncr = 1;
        }

        $formattedIncr = str_pad($nextIncr, 4, '0', STR_PAD_LEFT);
        $dsNumber = $prefix . $formattedIncr;

        Log::debug("📦 Generated DS Number: $dsNumber (Last: $last)");

        return $dsNumber;
    }

    public function importDs(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv'
        ]);

        $import = new DsInputImport();
        Excel::import($import, $request->file('file'));

        $successCount = $import->getSuccessCount();
        $failedRows = $import->getFailedRows();

        if ($successCount === 0 && count($failedRows) > 0) {
            return redirect()->back()->with('error', '❌ Tidak ada data berhasil diimpor.')
                                 ->with('failed_rows', $failedRows);
        }

        if ($successCount > 0 && count($failedRows) > 0) {
            return redirect()->back()->with('warning', "⚠️ {$successCount} data berhasil diimpor. Sebagian gagal.")
                                     ->with('failed_rows', $failedRows);
        }

        return redirect()->back()->with('success', "✅ Berhasil mengimpor {$successCount} data.");
    }
}

class SimpleArrayImport implements ToArray
{
    public function array(array $array)
    {
        return $array;
    }   
}