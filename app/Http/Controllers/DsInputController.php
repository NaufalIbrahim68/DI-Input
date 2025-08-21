<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DsInput;
use App\Models\DiInputModel;
use App\Imports\DsInputImport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB; 
use Illuminate\Support\Facades\Log;

class DsInputController extends Controller
{
public function index(Request $request)
{
    $selectedDate = $request->query('tanggal');
    $query = DsInput::query();
    $diDataCount = 0;

    if (!empty($selectedDate)) {
        // Hitung jumlah DI untuk tanggal ini
        $diDataCount = DiInputModel::whereDate('di_received_date_string', $selectedDate)->count();

        // Filter DS berdasarkan tanggal
        $query->whereDate('di_received_date_string', $selectedDate);

        // Flash info jika DS kosong tapi ada DI
        if ($query->count() == 0 && $diDataCount > 0) {
            session()->flash('info', "Ada {$diDataCount} data DI untuk tanggal {$selectedDate}, tapi belum digenerate ke DS.");
        }
    }

    $dsInputs = $query->orderBy('ds_number')
        ->paginate(10)
        ->appends($request->only(['tanggal']));

    return view('ds_input.index', compact('dsInputs', 'selectedDate', 'diDataCount'));
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
        return redirect()->back()->with('error', "Tidak ada data DI untuk tanggal {$selectedDate}.");
    }

    $datePrefix = Carbon::parse($selectedDate)->format('ymd');

    // Ambil nomor DS terakhir untuk tanggal itu
    $lastDS = DsInput::where('ds_number', 'like', "DS-{$datePrefix}-%")
        ->orderByDesc('ds_number')
        ->value('ds_number');

    $nextIncr = $lastDS ? ((int)substr($lastDS, -4)) + 1 : 0;

    foreach ($dataDI as $row) {
        $dsNumber = "DS-{$datePrefix}-" . str_pad($nextIncr, 4, '0', STR_PAD_LEFT);
        $nextIncr++;

        if (!DsInput::where('ds_number', $dsNumber)
            ->where('supplier_part_number', $row->supplier_part_number)
            ->exists()) {
            DsInput::create([
                'ds_number' => $dsNumber,
                'gate' => $row->gate,
                'supplier_part_number' => $row->supplier_part_number,
                'qty' => $row->qty,
                'di_type' => $row->di_type,
                'di_received_time' => $row->di_received_time,
                'di_received_date_string' => $row->di_received_date_string,
                'flag' => 0,
            ]);
        }
    }

    // Redirect ke index dengan tanggal yang sama supaya info dan tabel DS sinkron
    return redirect()->route('ds_input.index', ['tanggal' => $selectedDate])
                     ->with('success', "DS untuk tanggal {$selectedDate} berhasil digenerate.");
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