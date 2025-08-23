<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DsInput;
use App\Models\DiInputModel;
use App\Imports\DsInputImport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB; 
use Barryvdh\DomPDF\Facade\Pdf;

class DsInputController extends Controller
{
    // ✅ Halaman Data DS
    public function index(Request $request)
    {
        $query = DsInput::query();

        // Filter tanggal
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween(DB::raw("DATE(di_received_date_string)"), [$request->start_date, $request->end_date]);
        }

        // Filter status
        if ($request->filled('status')) {
            $query->where('di_status', $request->status);
        }

        $ds = $query->orderBy('created_at', 'desc')->paginate(10);

        return view('Ds_Input.index', compact('ds'));
        // 🔑 Note: pake "Ds_Input" sesuai struktur folder kamu
    }

    // ✅ Form untuk generate DS
    public function generateForm()
    {
        return view('Ds_Input.generate_ds');
    }

    // ✅ Generate DS dari data DI
    public function generate(Request $request)
    {
        $request->validate([
            'selected_date' => 'required|date',
        ]);

        $selectedDate = $request->input('selected_date');

        // Ambil semua DI untuk tanggal itu
        $dataDI = DiInputModel::whereDate('di_received_date_string', $selectedDate)->get();
        if ($dataDI->isEmpty()) {
            return redirect()->back()->with('error', "Tidak ada data DI untuk tanggal {$selectedDate}.");
        }

        // Ambil semua DS yang sudah ada untuk tanggal itu
        $existingDS = DsInput::whereDate('di_received_date_string', $selectedDate)
            ->get()
            ->keyBy('supplier_part_number');

        $datePrefix = Carbon::parse($selectedDate)->format('dmy');

        // Ambil nomor DS terakhir
        $lastDSNumber = DsInput::where('ds_number', 'like', "DS-{$datePrefix}-%")
            ->orderByDesc('ds_number')
            ->value('ds_number');

        $nextIncr = $lastDSNumber ? ((int)substr($lastDSNumber, -5)) + 1 : 1;

        foreach ($dataDI as $row) {
            $supplierPN = $row->supplier_part_number;

            // Skip jika sudah ada DS dengan supplier_part_number ini
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
                'flag_prep' => 0,
            ]);
        }

        return redirect()->route('ds_input.index', ['tanggal' => $selectedDate])
            ->with('success', "DS untuk tanggal {$selectedDate} berhasil digenerate.");
    }

    // ✅ Import DS dari Excel
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

    // ✅ Edit DS
    public function edit(Request $request, $ds_number)
    {
        $ds = DsInput::where('ds_number', $ds_number)->firstOrFail();
        return view('Ds_Input.dn_form', compact('ds'));
    }

    // ✅ Update DS
    public function update(Request $request, $ds_number)
    {
        $ds = DsInput::where('ds_number', $ds_number)->firstOrFail();

        $request->validate([
            'gate' => 'required|string|max:50',
            'di_type' => 'nullable|string|max:50',
            'supplier_part_number' => 'required|string|max:100',
            'di_received_date_string' => 'nullable|date',
            'di_received_time' => 'nullable',
            'flag_prep' => 'required|boolean',
            'qty' => 'required|integer|min:1',
        ]);

        $ds->update($request->only([
            'gate', 'di_type', 'supplier_part_number', 
            'di_received_date_string', 'di_received_time', 
            'flag_prep', 'qty'
        ]));

        return redirect()
            ->route('ds_input.index', $request->only(['tanggal','page']))
            ->with('success', "Data DS {$ds_number} berhasil diperbarui.");
    }

    // ✅ Hapus DS
    public function destroy(Request $request, $ds_number)
    {
        $dsInput = DsInput::where('ds_number', $ds_number)->firstOrFail();
        $dsInput->delete();

        return redirect()
            ->route('ds_input.index', $request->only(['tanggal','page']))
            ->with('success', "Data DS {$ds_number} berhasil dihapus.");
    }

    // ✅ Export PDF
    public function exportPdf(Request $request)
    {
        $selectedDate = $request->query('tanggal');

        $query = DsInput::query();
        if ($selectedDate) {
            $query->whereDate('di_received_date_string', $selectedDate);
        }

        $dsData = $query->orderBy('ds_number')->get();

        $pdf = Pdf::loadView('Ds_Input.pdf', compact('dsData', 'selectedDate'))
                  ->setPaper('a4', 'landscape');

        return $pdf->download("data-ds-{$selectedDate}.pdf");
    }
}
