<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DiInputModel;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function dashboard(Request $request)
    {
        $tanggal = null;
        $supplierPartNumber = $request->input('supplier_part_number');

        // Parsing tanggal jika ada
     if ($request->filled('tanggal')) {
    try {
        // parsing sesuai format input yang dikirim dari form (d-m-Y)
        $tanggal = Carbon::createFromFormat('d-m-Y', $request->input('tanggal'))->format('d-m-Y');
    } catch (\Exception $e) {
        return back()->withErrors(['tanggal' => 'Format tanggal tidak valid (gunakan dd-mm-yyyy)']);
    }
}

$query = DiInputModel::query();

if ($tanggal) {
    $query->where('di_received_date_string', $tanggal);
}

        if ($supplierPartNumber) {
            $query->whereRaw("REPLACE(supplier_part_number, ' ', '') LIKE ?", ['%' . str_replace(' ', '', $supplierPartNumber) . '%']);
        }

        $query->orderByDesc('di_received_date_string');

        $timeline = (!$tanggal && !$supplierPartNumber) ? $query->take(5)->get() : $query->get();
        $totalQty = $timeline->sum('qty');

        // Ambil semua data untuk chart
        $fullChartData = DiInputModel::selectRaw("
            CONVERT(VARCHAR, di_received_date_string, 23) as tanggal,
            SUM(ISNULL(qty, 0)) as total_qty
        ")
        ->whereNotNull('di_received_date_string')
        ->groupBy('di_received_date_string')
        ->orderBy('tanggal')
        ->get();

        // Ambil 5 data terakhir untuk default chart
        $last5ChartData = $fullChartData->slice(-5);

        // Bikin grup tanggal dan data per 5 entry
$groupedChartData = collect($fullChartData)
    ->chunk(5)
    ->map(function ($chunk) {
        return [
            'labels' => $chunk->pluck('tanggal')->toArray(),
            'data' => $chunk->pluck('total_qty')->toArray(),
        ];
    })
    ->reverse() // biar yang terbaru di atas
    ->values();


        return view('dashboard', [
    'timeline' => $timeline,
    'chartLabels' => $groupedChartData->first()['labels'] ?? [],
    'chartData' => $groupedChartData->first()['data'] ?? [],
    'groupedChartData' => $groupedChartData,
    'totalQty' => $totalQty,
]);
    }
}
