<?php

namespace App\Http\Controllers;

use App\Models\DiInputModel;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\DeliveryImport;
use Illuminate\Support\Facades\DB;

class DiInputController extends Controller
{
    public function index(Request $request)
    {
        $data = DiInputModel::all();
        return view('DI_Input.index', compact('data'));
    }

    public function create()
    {
        return view('DI_Input.form');
    }   

    public function store(Request $request)
    {
        $request->validate([
            'Gate' => 'required',
            'PO_Number' => 'required',
            'Supplier_PN'=> 'required',
            'Qty' => 'required|integer',
            'DI_Type'=> 'required',
            'Deliv_Date' => 'required|date',
        ]);

        DiInputModel::create($request->all());

        return redirect()->route('DI_Input.index')->with('message', 'Data berhasil ditambahkan!');
    }

    // Import Excel
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        Excel::import(new DeliveryImport, $request->file('file'));

        return redirect()->route('DI_Input.index')->with('message', 'Data berhasil diimpor dari Excel!');
    }
     public function dashboard()
{
    // Simulasi ambil data dari database
    $summary = [
        'total_di' => DiInputModel::count(),
        'completed_di' => DiInputModel::where('di_status', 'Created')->count(),
        'pending_di' => DiInputModel::where('di_status', 'Pending')->count(),
    ];

    $monthlyData = [
        'labels' => ['Jan', 'Feb', 'Mar', 'Apr'],
        'values' => [80, 120, 90, 150],
    ];

     $timeline = DiInputModel::orderByDesc(
    DB::raw("CONVERT(datetime, CONVERT(varchar, di_created_date, 23) + ' ' + di_created_time)")
)
->take(5)
->get(['di_no', 'di_status', 'di_created_date', 'di_created_time']);

    return view('DI_Input.dashboard', compact('summary', 'monthlyData', 'timeline'));
}

}

