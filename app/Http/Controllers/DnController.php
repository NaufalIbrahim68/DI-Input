<?php

namespace App\Http\Controllers;

use App\Models\Dn_Input;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\DnExport;
use Maatwebsite\Excel\Facades\Excel;

class DnController extends Controller
{   
    public function index(Request $request)
    {
       $selectedDate = $request->input('selected_date');
$dnData = collect(); // default kosong

if (!empty($selectedDate)) {
    // baru query dijalankan
}
        // hanya jalankan query kalau ada tanggal
        if (!empty($selectedDate)) {
            $dnData = DB::table('dn_input as d')
                ->leftJoin('ds_input as ds', 'd.ds_number', '=', 'ds.ds_number')
                ->select(
                    'd.id',
                    'd.ds_number',
                    'd.dn_number',
                    'd.qty_dn',
                    'd.created_at',
                    'd.updated_at',
                    'ds.qty as qty_ds', // ambil qty dari DS
                    'ds.di_received_date_string'
                )
                ->whereDate('ds.di_received_date_string', $selectedDate)
                ->get();
        }

        return view('dn.index', compact('dnData', 'selectedDate'));
    }

    public function create($ds_number)
    {
        $ds = DB::table('ds_input')->where('ds_number', $ds_number)->first();

        if (!$ds) {
            return redirect()->route('dn.index')->with('error', 'DS tidak ditemukan');
        }

        return view('Ds_Input.dn_form', compact('ds'));
    }

    public function store(Request $request, $ds_number)
    {
        $request->validate([
            'dn_number' => 'required|string',
            'qty_dn'    => 'required|integer|min:1',
        ]);
$ds = DB::table('ds_input')->where('ds_number', $ds_number)->first();

Dn_Input::create([
    'ds_number' => $ds_number,
    'dn_number' => $request->dn_number,
    'qty_dn'    => $request->qty_dn,  
    'qty'       => $ds->qty, // qty DS
]);
        return redirect()->route('dn.create', $ds_number)
                        ->with('success', 'Data DN berhasil disimpan!');
    }

    public function exportPdf(Request $request)
{
    $selectedDate = $request->input('selected_date');
    $dnData = collect();

    if (!empty($selectedDate)) {
        $dnData = DB::table('dn_input as d')
            ->leftJoin('ds_input as ds', 'd.ds_number', '=', 'ds.ds_number')
            ->select(
                'd.id',
                'd.ds_number',
                'd.dn_number',
                'd.qty_dn',
                'd.created_at',
                'd.updated_at',
                'ds.qty as qty_ds',
                'ds.di_received_date_string'
            )
            ->whereDate('ds.di_received_date_string', $selectedDate)
            ->get();
    } else {
        // kalau tidak ada filter, ambil semua
        $dnData = DB::table('dn_input as d')
            ->leftJoin('ds_input as ds', 'd.ds_number', '=', 'ds.ds_number')
            ->select(
                'd.id',
                'd.ds_number',
                'd.dn_number',
                'd.qty_dn',
                'd.created_at',
                'd.updated_at',
                'ds.qty as qty_ds',
                'ds.di_received_date_string'
            )
            ->get();
    }

    $pdf = Pdf::loadView('dn.pdf', compact('dnData', 'selectedDate'))
              ->setPaper('a4', 'landscape');

    return $pdf->download('DN_Data_'.now()->format('Ymd_His').'.pdf');
}




public function export(Request $request)
{
    $selectedDate = $request->query('selected_date'); // atau 'tanggal', sesuaikan dengan input filter

    if (!$selectedDate) {
        return redirect()->back()->with('error', 'Harap pilih tanggal terlebih dahulu.');
    }

    return Excel::download(new DnExport($selectedDate), 'dn_export_'.now()->format('Ymd_His').'.xlsx');
}

}
