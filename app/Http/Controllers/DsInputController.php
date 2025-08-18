<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DsInputModel;
use App\Models\DiInputModel;
use App\Models\DsInput;
use App\Imports\DsInputImport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class DsInputController extends Controller
{
    public function index(Request $request)
{
    $selectedDate = $request->input('selected_date');
    $statusFilter = $request->input('status', []); // array, default kosong
    $search       = $request->input('search');

    $dsInputs = DsInput::when($selectedDate, function ($query) use ($selectedDate) {
            $query->whereDate('di_received_date_string', $selectedDate);
        })
        ->when(!empty($statusFilter), function ($query) use ($statusFilter) {
            $query->whereIn('di_status', $statusFilter);
        })
        ->when($search, function ($query) use ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('ds_number', 'like', "%{$search}%")
                  ->orWhere('gate', 'like', "%{$search}%")
                  ->orWhere('supplier_part_number', 'like', "%{$search}%");
            });
        })
        ->orderBy('ds_number', 'asc') // urut dari DS terkecil
        ->paginate(10);

    return view('ds_input.index', compact('dsInputs', 'selectedDate', 'statusFilter', 'search'));
}


   public function generate(Request $request)
{
    $request->validate([
        'selected_date' => 'required|date',
        'status' => 'nullable|array',
        'status.*' => 'string'
    ]);

    $selectedDate = $request->input('selected_date');
    $statusFilter = $request->input('status', []);

    // Ambil data dari di_input dengan filter status
    $query = DiInputModel::where('di_received_date_string', $selectedDate);

    if (!empty($statusFilter)) {
        $query->whereIn('di_status', $statusFilter);
    }

$dataDI = $query->orderBy('gate', 'asc')->get();

    if ($dataDI->isEmpty()) {
        return redirect()
            ->route('ds_input.index', ['selected_date' => $selectedDate])
            ->with('error', "Tidak ada data untuk tanggal {$selectedDate}.");
    }

    $datePrefix = Carbon::parse($selectedDate)->format('ymd');

    $lastDS = DsInput::where('ds_number', 'like', "DS-{$datePrefix}-%")
        ->orderByDesc('ds_number')
        ->value('ds_number');

    $nextIncr = $lastDS ? ((int)substr($lastDS, -4)) + 1 : 1;

    foreach ($dataDI as $row) {
        $dsNumber = "DS-{$datePrefix}-" . str_pad($nextIncr, 4, '0', STR_PAD_LEFT);
        $nextIncr++;

        $statusRaw = strtolower(trim($row->di_status ?? ''));
        $statusMap = [
            'created'  => 'Created',
            'used'     => 'Used',
            'received' => 'Received',
        ];
        $finalStatus = $statusMap[$statusRaw] ?? 'Created';

        $exists = DsInput::where('ds_number', $dsNumber)
            ->where('supplier_part_number', $row->supplier_part_number)
            ->exists();

        if (!$exists) {
            DsInput::create([
                'ds_number' => $dsNumber,
                'gate' => $row->gate,
                'supplier_part_number' => $row->supplier_part_number,
                'qty' => $row->qty,
                'di_type' => $row->di_type,
                'di_status' => $finalStatus,
                'di_received_time' => $row->di_received_time,
                'di_received_date_string' => $row->di_received_date_string,
                'flag' => 0,
            ]);
        }
    }

   return redirect()->route('ds_input.index', [
    'selected_date' => $selectedDate,
    'status'        => $statusFilter,
]);

}

    public function generateFromDate(Request $request)
    {
        return $this->generate($request);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls'
        ]);

        $import = new DsInputImport();
        Excel::import($import, $request->file('file'));

        return back()->with('success', "Import selesai: {$import->getSuccessCount()} data berhasil.");
    }
public function update(Request $request, $ds_number)
{
    $request->validate([
        'gate' => 'required|string',
        'supplier_part_number' => 'required|string',
        'qty' => 'required|integer',
        'di_type' => 'required|string',
        'di_status' => 'required|string',
        'di_received_date_string' => 'required|date',
        'di_received_time' => 'required',
        'flag' => 'required|integer',
    ]);

    $dsInput = DsInput::where('ds_number', $ds_number)->firstOrFail();

    $dsInput->update([
        'gate' => $request->gate,
        'supplier_part_number' => $request->supplier_part_number,
        'qty' => $request->qty,
        'di_type' => $request->di_type,
        'di_status' => $request->di_status,
        'di_received_date_string' => $request->di_received_date_string,
        'di_received_time' => $request->di_received_time,
        'flag' => $request->flag,
    ]);

    return redirect()->route('ds_input.index', [
        'page' => $request->input('page', 1),
    ])->with('success', 'Data DS berhasil diperbarui.');
}

public function destroy($ds_number)
{
    $dsInput = DsInput::where('ds_number', $ds_number)->firstOrFail();
    $dsInput->delete();

    return redirect()->route('ds_input.index')
        ->with('success', "Data DS {$ds_number} berhasil dihapus.");
}

public function createDn($ds_number)
{
    $ds = DsInput::where('ds_number', $ds_number)->firstOrFail();
    return view('ds_input.dn_form', compact('ds'));
}

public function storeDn(Request $request, $ds_number)
{
    $request->validate([
        'dn_number' => 'required|string|max:50',
    ]);

    $ds = DsInput::where('ds_number', $ds_number)->firstOrFail();
    $ds->dn_number = $request->dn_number;
    $ds->save();

    return redirect()->route('ds_input.index')->with('success', 'Nomor DN berhasil disimpan.');
}

}
