<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DsInputController extends Controller
{
    // 🔍 Menampilkan data ds_input (TOP 1000)
    public function index()
    {
        $dsInputs = DB::table('ds_input')->take(1000)->get();
        return view('ds_input.index', compact('dsInputs'));
    }

    // 📝 Menyimpan data baru ke tabel ds_input
    public function store(Request $request)
    {
        $request->validate([
            'gate' => 'required|string',
            'supplier_part_number' => 'required|string',
            'qty' => 'required|integer|min:1',
            'di_type' => 'required|string',
            'di_status' => 'required|string',
        ]);

        try {
            $dsNumber = $this->generateDsNumber();

            DB::table('ds_input')->insert([
                'ds_number' => $dsNumber,
                'gate' => $request->gate,
                'supplier_part_number' => $request->supplier_part_number,
                'qty' => intval($request->qty),
                'di_type' => $request->di_type,
                'di_status' => $request->di_status,
                'di_received_date' => Carbon::now()->toDateString(),
                'di_received_time' => Carbon::now()->toTimeString(),
                'created_at' => now(),
                'updated_at' => now(),
                'flag' => $request->flag ?? null,
            ]);

            return redirect()->back()->with('success', '✅ Data berhasil disimpan!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', '❌ Gagal menyimpan data: ' . $e->getMessage());
        }
    }

    // 🔢 Generate kode DS number format: DS-YYYYMMDD-0001
    private function generateDsNumber()
    {
        $today = Carbon::now()->format('Ymd');
        $prefix = "DS-{$today}-";

        $lastEntry = DB::table('ds_input')
            ->whereDate('created_at', Carbon::today())
            ->where('ds_number', 'like', "$prefix%")
            ->orderByDesc('ds_number')
            ->first();

        $nextIncrement = 1;
        if ($lastEntry) {
            $lastNumber = (int) substr($lastEntry->ds_number, -4);
            $nextIncrement = $lastNumber + 1;
        }

        $formattedIncrement = str_pad($nextIncrement, 4, '0', STR_PAD_LEFT);

        return $prefix . $formattedIncrement;
    }
}
