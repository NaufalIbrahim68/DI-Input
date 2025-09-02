<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DiInputModel;
use App\Models\DsInput; 
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function dashboard(Request $request)
    {
        $tanggal = null;
        $supplierPartNumber = $request->input('supplier_part_number');

        // Ambil tanggal langsung dari input (format Y-m-d)
        if ($request->filled('tanggal')) {
            $tanggalInput = $request->input('tanggal'); 
            $tanggal = Carbon::createFromFormat('Y-m-d', $tanggalInput)->format('Y-m-d');
        }

        // ==========================
        // Query utama (untuk timeline & total qty)
        // ==========================
        $baseQuery = DiInputModel::query();

        if ($tanggal) {
            $baseQuery->where('di_received_date_string', $tanggal);
        }

        if ($supplierPartNumber) {
            $baseQuery->whereRaw(
                "REPLACE(supplier_part_number, ' ', '') LIKE ?",
                ['%' . str_replace(' ', '', $supplierPartNumber) . '%']
            );
        }

        $baseQuery->orderByDesc('di_received_date_string');

        // Cek apakah ada filter
        $isFiltered = $tanggal || $supplierPartNumber;

        // Kalau ada filter → paginate(10), kalau tidak → ambil 5 data (tanpa pagination)
        if ($isFiltered) {
            $timeline = (clone $baseQuery)->paginate(10)->appends($request->all());
        } else {
            $timeline = (clone $baseQuery)->limit(5)->get();
        }

        // Hitung total qty dari query yang sudah difilter (bukan dari timeline)
        $totalQty = (clone $baseQuery)->sum('qty');

        // ==========================
        // Data untuk chart (group berdasarkan tanggal di DB, format d-m-Y)
        // ==========================
        $fullChartData = DiInputModel::selectRaw("
                di_received_date_string as tanggal,
                SUM(ISNULL(qty, 0)) as total_qty
            ")
            ->whereNotNull('di_received_date_string')
            ->groupBy('di_received_date_string')
            ->orderBy('tanggal')
            ->whereRaw("TRY_CONVERT(date, di_received_date_string, 105) >= DATEADD(MONTH, -3, GETDATE())")
            ->get();

        $groupedChartData = collect($fullChartData)
            ->chunk(5)
            ->map(function ($chunk) {
                return [
                    'labels' => $chunk->pluck('tanggal')->toArray(),
                    'data' => $chunk->pluck('total_qty')->toArray(),
                ];
            })
            ->reverse()
            ->values();

       

$sevenDaysAgo = Carbon::now()->subDays(7)->format('Y-m-d');

$dsInputs = DsInput::where('di_received_date_string', '>=', $sevenDaysAgo)->get();

$completed = 0;
$partial = 0;

foreach ($dsInputs as $ds) {
    $qtyAgv = (int) ($ds->qty_agv ?? 0);
    $qtyDs  = (int) ($ds->qty ?? 0);

    if ($qtyAgv == $qtyDs && $qtyDs > 0) {
        $completed++;
    } else {
        $partial++;
    }
}

$statusData = [
    'completed' => $completed,
    'partial'   => $partial,
];

return view('dashboard', [
    'timeline' => $timeline,
    'chartLabels' => $groupedChartData->first()['labels'] ?? [],
    'chartData' => $groupedChartData->first()['data'] ?? [],
    'groupedChartData' => $groupedChartData,
    'totalQty' => $totalQty,
    'isFiltered' => $isFiltered,
    'statusData' => $statusData, // sekarang pakai completed/partial
]);
    }
}
