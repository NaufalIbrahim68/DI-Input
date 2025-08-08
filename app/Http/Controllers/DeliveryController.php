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

            $references = $this->loadReferences();
            Log::info("📚 Loaded " . $references->count() . " reference data");

            $result = $this->processExcelData($data[0], $references);

            return $this->buildResponse($result);
        } catch (\Exception $e) {
            Log::error("❌ Import failed: " . $e->getMessage());
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

    private function processExcelData(array $rows, $references)
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

                if (!$this->validateRequiredFields($row)) {
                    Log::warning("⚠️ Lewati baris $index karena field wajib kosong.");
                    $failedRows[] = $index + 1;
                    $failedCount++;
                    continue;
                }

                try {
                    $status = $this->processRow($row, $references);

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

    private function processRow(array $row, $references)
    {
        $diNo = trim($row[0] ?? '');

        if (empty($diNo) || strtolower($diNo) === 'di no') {
            throw new \Exception("❌ DI No kosong atau tidak valid (mungkin header ikut terproses)");
        }

        $supplierPN = $this->normalizePartNumber($row[3] ?? '');
        $reference = $references->get($supplierPN);

        $updateData = $this->prepareUpdateData($row, $reference);
        $updateData['di_no'] = $diNo;

        // cek duplikasi
        $existing = DiInputModel::where('di_no', $diNo)->exists();

        if (!$existing) {
            DiInputModel::create($updateData);
            Log::debug("🆕 DiInput baru disimpan: $diNo");
        } else {
            Log::info("⚠️ DI No $diNo sudah ada, tapi tetap dilanjut insert ke ds_input.");
        }

        // tetap insert ke ds_input

        $gate = $row[1] ?? null;
$supplierPartNumber = $row[2] ?? null;
$diReceivedDate = !empty($row[6]) ? \Carbon\Carbon::parse($row[6])->format('Y-m-d') : null;

// Cek apakah duplikat
$isDuplicate = DB::table('ds_input')
    ->where('gate', $gate)
    ->where('supplier_part_number', $supplierPartNumber)
    ->whereDate('di_received_date_string', $diReceivedDate)
    ->exists();

if ($isDuplicate) {
    Log::warning("⚠️ Duplikat DS terdeteksi: $gate - $supplierPartNumber - $diReceivedDate");
    throw new \Exception("Duplikat DS: kombinasi gate, supplier_part_number, dan tanggal sudah ada.");
}

       try {
    DB::table('ds_input')->insert([
        'ds_number' => $this->generateDsNumber(),
        'gate' => $row[1] ?? null,
        'supplier_part_number' => $row[2] ?? null,
        'qty' => $this->parseQty($row[3] ?? 0),
        'di_type' => $row[4] ?? null,
        'di_status' => $row[5] ?? null,
        'di_received_date_string' => !empty($row[6]) ? \Carbon\Carbon::parse($row[7]) : null,
        'di_received_time' => $row[7] ?? null,
        'created_at' => now(),
        'updated_at' => now(),
       'flag' => 0,
    ]);
    Log::info("🟢 Berhasil insert ke ds_input untuk DI No: $diNo");
} catch (\Exception $e) {
    Log::error("❌ Gagal insert ke ds_input untuk DI No: $diNo | Error: " . $e->getMessage());
    throw new \Exception("Gagal insert ke ds_input: " . $e->getMessage());
}

    

        // kembalikan status sukses insert
        return 'created';
    }

    private function prepareUpdateData(array $row, $reference = null)
    {
        $updateData = [
            'di_no' => $row[0] ?? null,
            'gate' => $row[1] ?? null,
            'po_number' => $row[2] ?? null,
            'supplier_part_number' => $row[3] ?? null,
            'supplier_part_number_desc' => $row[4] ?? null,
            'qty' => $this->parseQty($row[5] ?? 0),
            'di_type' => $row[6] ?? null,
            'di_received_date_string' => \Carbon\Carbon::parse($row[7])->format('d-M-Y'),
            'di_received_time' => $row[8] ?? null,
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

    private function validateRequiredFields(array $row)
    {
        $diNo = trim($row[0] ?? '');
        return !empty($diNo) && strtolower($diNo) !== 'di no';
    }

    private function buildResponse(array $result)
    {
        $created = $result['created'];
        $failed = $result['failed'];
        $failedRows = $result['failed_rows'];

        $messageParts = [];

        if ($created > 0) {
            $messageParts[] = "✅ $created data berhasil diimpor";
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
            return back()->with('error', '❌ Tidak ada data berhasil diimpor.');
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

    $last = DB::table('ds_input')
        ->whereDate('created_at', now()->toDateString())
        ->where('ds_number', 'like', "$prefix%")
        ->orderByDesc('ds_number')
        ->value('ds_number');

    if ($last) {
        $lastIncr = (int) substr($last, -4);
        $nextIncr = $lastIncr + 1;
    } else {
        $nextIncr = 1;
    }

    $formattedIncr = str_pad($nextIncr, 4, '0', STR_PAD_LEFT);
    $dsNumber = $prefix . $formattedIncr;

    Log::debug("📦 Generated DS Number: $dsNumber");

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

}class SimpleArrayImport implements ToArray
{
    public function array(array $array)
    {
        return $array;
    }   
    
}
