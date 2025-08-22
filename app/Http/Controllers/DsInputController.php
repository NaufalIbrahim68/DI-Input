<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DsInput;
use App\Models\DiInputModel;
use App\Imports\DsInputImport;
use App\Models\Dn_Input;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB; 
use Illuminate\Support\Facades\Log;

class DsInputController extends Controller
{
public function index(Request $request)
{
    $selectedDate = $request->query('tanggal');
    $dsInputs = null; // default null jika tidak ada tanggal

    if (!empty($selectedDate)) {
        $query = DsInput::query();

        // Filter DS berdasarkan tanggal
        $query->whereDate('di_received_date_string', $selectedDate);

        $dsInputs = $query->orderBy('ds_number')
            ->paginate(10)
            ->through(function ($item) {
                // Tambahkan properti format tanggal
                $item->di_received_date_string_formatted = Carbon::parse($item->di_received_date_string)->format('d/m/Y');
                return $item;
            })
            ->appends($request->only(['tanggal']));
    }

    return view('ds_input.index', compact('dsInputs', 'selectedDate'));
}
public function generate(Request $request)
{
    $request->validate([
        'selected_date' => 'required|date',
    ]);

    $selectedDate = $request->input('selected_date');

    // Ambil semua DI untuk tanggal itu
    $dataDI = DiInputModel::where('di_received_date_string', $selectedDate)->get();
    if ($dataDI->isEmpty()) {
        return redirect()->back()->with('error', "Tidak ada data Ds untuk tanggal {$selectedDate}.");
    }

    // Ambil semua DS yang sudah ada untuk tanggal itu
    $existingDS = DsInput::whereDate('di_received_date_string', $selectedDate)
        ->get()
        ->keyBy('supplier_part_number'); // gunakan supplier_part_number sebagai key

    $datePrefix = Carbon::parse($selectedDate)->format('dmy');

    // Ambil nomor DS terakhir yang sudah ada untuk tanggal itu
    $lastDSNumber = DsInput::where('ds_number', 'like', "DS-{$datePrefix}-%")
        ->orderByDesc('ds_number')
        ->value('ds_number');

    // Tentukan nomor increment selanjutnya
    $nextIncr = $lastDSNumber ? ((int)substr($lastDSNumber, -5)) + 1 : 1;

    foreach ($dataDI as $row) {
        $supplierPN = $row->supplier_part_number;

        // Skip jika DS untuk supplier_part_number ini sudah ada
        if ($existingDS->has($supplierPN)) {
            continue;
        }

        $dsNumber = "DS-{$datePrefix}-" . str_pad($nextIncr, 5, '0', STR_PAD_LEFT);
        $nextIncr++;

        DsInput::create([
            'ds_number' => $dsNumber,
            'gate' => $row->gate,
            'supplier_part_number' => $row->supplier_part_number,
            'qty' => $row->qty,
            'di_type' => $row->di_type,
            'di_received_time' => $row->di_received_time,
            'di_received_date_string' => $row->di_received_date_string,
            'flag' => 0,
            'flag_prep' => 0, // otomatis diset 0, tidak ditampilkan di blade
        ]);
    }

    // Langsung redirect tanpa notifikasi
    return redirect()->route('ds_input.index', ['tanggal' => $selectedDate]);
}
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls'
        ]);

        $import = new DsInputImport();
        Excel::import($import, $request->file('file'));

        $successCount = $import->getSuccessCount();
        $totalRows = count(Excel::toArray(new DsInputImport(), $request->file('file'))[0]);
        $failedCount = $totalRows - $successCount;

        return back()->with('success', "Import selesai: {$successCount} data berhasil, {$failedCount} gagal.");
    }

    public function edit(Request $request, $ds_number)
    {
        $ds = DsInput::where('ds_number', $ds_number)->firstOrFail();
        $selectedDate = $request->input('tanggal');
        $page = $request->input('page', 1);

        $query = DsInput::query();

       if (!empty($selectedDate)) {
    $query->where(DB::raw("CAST(di_received_date_string AS DATE)"), $selectedDate);
}

        $dsInputs = $query->orderBy('ds_number')
            ->paginate(10)
            ->appends($request->only(['tanggal']));

        return view('ds_input.index', compact('ds', 'dsInputs', 'selectedDate', 'page'));
    }

    public function update(Request $request, $ds_number)
    {
        $ds = DsInput::where('ds_number', $ds_number)->firstOrFail();

        $request->validate([
            'gate' => 'required|string|max:50',
            'di_type' => 'nullable|string|max:50',
            'supplier_part_number' => 'required|string|max:100',
            'di_received_date_string' => 'nullable|date',
            'di_received_time' => 'nullable',
            'flag' => 'required|boolean',
            'qty' => 'required|integer|min:1',
        ]);

        $ds->update($request->only([
            'gate', 'di_type', 'supplier_part_number', 
            'di_received_date_string', 'di_received_time', 
            'flag', 'qty'
        ]));

        return redirect()
            ->route('ds_input.index', $request->only(['tanggal','page']))
            ->with('success', "Data DS {$ds_number} berhasil diperbarui.");
    }

    public function destroy(Request $request, $ds_number)
    {
        $dsInput = DsInput::where('ds_number', $ds_number)->firstOrFail();
        $dsInput->delete();

        return redirect()
            ->route('ds_input.index', $request->only(['tanggal','page']))
            ->with('success', "Data DS {$ds_number} berhasil dihapus.");
    }


public function createDn($ds)
{
    // Ambil data DS
    $dsInput = DsInput::where('ds_number', $ds)->firstOrFail();

    // Tampilkan view form DN, kirim data DS
   return view('DS_Input.dn_form', ['ds' => $dsInput]);
}

public function storeDn(Request $request, $ds)
{
    $request->validate([
        'dn_number' => 'required|string|unique:dn_inputs,dn_number',
        'qty_dn' => 'required|integer|min:1',
    ]);

    // Ambil DS
    $dsInput = DsInput::where('ds_number', $ds)->firstOrFail();

    // Simpan ke tabel DN (buat model DnInput atau sesuai nama tabelmu)
    Dn_Input::create([
        'ds_number' => $dsInput->ds_number,
        'dn_number' => $request->dn_number,
        'qty_dn' => $request->qty_dn,
        'supplier_part_number' => $dsInput->supplier_part_number,
        'gate' => $dsInput->gate,
        'di_type' => $dsInput->di_type,
        'di_received_date_string' => $dsInput->di_received_date_string,
        'di_received_time' => $dsInput->di_received_time,
    ]);

    return redirect()->route('ds_input.index')->with('success', "DN {$request->dn_number} berhasil dibuat dari DS {$dsInput->ds_number}.");
}
    // Method untuk debugging
    public function debugData()
    {
        return response()->json([
            'di_sample_dates' => DiInputModel::select('di_received_date_string')
                ->distinct()
                ->limit(20)
                ->pluck('di_received_date_string'),
            'ds_sample_dates' => DsInput::select('di_received_date_string')
                ->distinct()
                ->limit(20)
                ->pluck('di_received_date_string'),
            'di_count' => DiInputModel::count(),
            'ds_count' => DsInput::count()
        ]);
    }

    public function testFilter(Request $request)
    {
        $selectedDate = $request->query('date', '2025-08-21'); // Default date for testing
        
        $diData = DiInputModel::whereDate('di_received_date_string', $selectedDate)->get();
        $dsData = DsInput::whereDate('di_received_date_string', $selectedDate)->get();
        
        return response()->json([
            'selected_date' => $selectedDate,
            'di_found' => $diData->count(),
            'ds_found' => $dsData->count(),
            'di_sample' => $diData->take(3),
            'ds_sample' => $dsData->take(3)
        ]);
    }


}