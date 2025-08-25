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
use App\Exports\DsInputExport; 

class DsInputController extends Controller
{
    // ✅ Halaman Data DS
   
public function index(Request $request)
{
    $query = DsInput::query();

    // filter tanggal kalau ada
    if ($request->filled('tanggal')) {
        $query->whereDate('created_at', $request->tanggal);
    }

    $dsInputs = $query->orderBy('created_at', 'desc')->get(); // ambil semua data

    return view('ds_input.index', compact('dsInputs'));
}
    // ✅ Form untuk generate DS
  public function generateForm(Request $request)
    {
        // Ambil tanggal dari query ?generate_date=YYYY-MM-DD
        $generateDate = $request->query('generate_date');
        $dsInputs = collect();

        if ($generateDate) {
            $dsInputs = DsInput::whereDate('di_received_date_string', $generateDate)
                ->orderBy('ds_number')
                ->get();
        }

        // NOTE: sesuaikan nama view dengan lokasi file kamu
        return view('Ds_Input.generate_ds', compact('generateDate', 'dsInputs'));
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
   public function edit($ds_number)
{
    $ds = DsInput::where('ds_number', $ds_number)->firstOrFail();
    return view('Ds_Input.edit', compact('ds'));
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
    public function exportPdf()
{
    $dsInputs = DsInput::all();

    $pdf = Pdf::loadView('Ds_Input.pdf', compact('dsInputs'))
        ->setPaper('a4', 'landscape');

    return $pdf->download('ds_input.pdf');
}

  public function export(Request $request)
{
    $tanggal = $request->input('tanggal'); 
    return Excel::download(new DsInputExport($tanggal), 'ds_input.xlsx');
}

public function generate(Request $request)
{
    $request->validate([
        'generate_date' => 'required|date',
    ]);

    $date = $request->generate_date;

    try {
        DB::beginTransaction();

        // Ambil semua DI untuk tanggal tersebut
        $diData = DiInputModel::whereDate('di_received_date_string', $date)->get();

        if ($diData->isEmpty()) {
            return redirect()
                ->route('ds_input.generatePage', ['generate_date' => $date])
                ->with('error', 'Tidak ada data DI pada tanggal tersebut.');
        }

        // Prefix DS berdasarkan tanggal generate
        $prefix = Carbon::parse($date)->format('dmy');

        // Ambil DS terakhir untuk tanggal tersebut
        $last = DsInput::where('ds_number', 'like', "DS-$prefix-%")
            ->orderBy('ds_number', 'desc')
            ->first();

        $counter = $last ? intval(substr($last->ds_number, -4)) + 1 : 1;

        foreach ($diData as $di) {

            // Cek duplikat DS untuk supplier_part_number dan tanggal
            $exists = DsInput::where('supplier_part_number', $di->supplier_part_number)
                ->where('di_received_date_string', $di->di_received_date_string)
                ->exists();

            if ($exists) continue; // lewati jika sudah ada

            $dsNumber = "DS-$prefix-" . str_pad($counter++, 4, '0', STR_PAD_LEFT);

            DsInput::create([
                'ds_number'               => $dsNumber,
                'gate'                    => $di->gate ?? '-',
                'supplier_part_number'    => $di->supplier_part_number,
                'qty'                     => $di->qty ?? 0,
                'di_type'                 => $di->di_type ?? null,
                'di_status'               => $di->di_status ?? null,
                'di_received_date_string' => $di->di_received_date_string ?? $date,
                'di_received_time'        => $di->di_received_time ?? null,
                'flag_prep'               => $di->flag_prep ?? 0,
            ]);
        }

        DB::commit();

        return redirect()
            ->route('ds_input.generatePage', ['generate_date' => $date])
            ->with('success', 'Generate DS berhasil untuk tanggal ' . Carbon::parse($date)->format('d-m-Y'));

    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()
            ->route('ds_input.generatePage', ['generate_date' => $date])
            ->with('error', 'Generate gagal: ' . $e->getMessage());
    }
}
}