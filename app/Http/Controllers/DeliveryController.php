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
    const HEADER_ROWS_TO_SKIP = 5;
    const MAX_EXECUTION_TIME = 600;
    const MEMORY_LIMIT = '1024M';
    const CHUNK_SIZE = 1000;

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

        $supplierPN = $this->normalizePartNumber($row[6] ?? '');
        $reference = $references->get($supplierPN);

        $existing = DiInputModel::where('di_no', $diNo)->exists();

        if ($existing) {
            Log::info("⚠️ Data dengan DI No $diNo sudah ada, dianggap gagal.");
            return 'duplicate';
        }

        $updateData = $this->prepareUpdateData($row, $reference);
        $updateData['di_no'] = $diNo;

        DiInputModel::create($updateData);
        Log::debug("🆕 DiInput baru disimpan: $diNo");

        return 'created';
    }

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

    private function parseDate($date)
    {
        try {
            if (empty($date)) return null;
            if (is_numeric($date)) return Date::excelToDateTimeObject($date);
            if (is_string($date)) return \Carbon\Carbon::parse($date);
            return null;
        } catch (\Exception $e) {
            Log::warning("⚠️ Tanggal tidak valid: " . json_encode($date) . " - Error: " . $e->getMessage());
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
