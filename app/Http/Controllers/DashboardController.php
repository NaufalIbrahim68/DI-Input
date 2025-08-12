<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DiInputModel;

class DashboardController extends Controller
{
    public function dashboard(Request $request)
    {
        $tanggal = null;
        $supplierPartNumber = $request->input('supplier_part_number');

        // Ambil tanggal langsung dari input (sudah format Y-m-d)
      if ($request->filled('tanggal')) {
    // Ubah dari Y-m-d (input date) ke d-m-Y (format di DB)
    $tanggal = \Carbon\Carbon::createFromFormat('Y-m-d', $request->input('tanggal'))
        ->format('d-m-Y');
} else {
    $tanggal = null;
}

$query = DiInputModel::query();

if ($tanggal) {
    $query->where('di_received_date_string', $tanggal);
}

        // Filter supplier part number
        if ($supplierPartNumber) {
            $query->whereRaw(
                "REPLACE(supplier_part_number, ' ', '') LIKE ?",
                ['%' . str_replace(' ', '', $supplierPartNumber) . '%']
            );
        }

        $query->orderByDesc('di_received_date_string');

        // Jika tidak ada filter, ambil 5 terbaru
        $timeline = (!$tanggal && !$supplierPartNumber)
            ? $query->take(5)->get()
            : $query->get();

        $totalQty = $timeline->sum('qty');

        // Data untuk chart
        $fullChartData = DiInputModel::selectRaw("
            di_received_date_string as tanggal,
            SUM(ISNULL(qty, 0)) as total_qty
        ")
            ->whereNotNull('di_received_date_string')
            ->groupBy('di_received_date_string')
            ->orderBy('tanggal')
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

        return view('dashboard', [
            'timeline' => $timeline,
            'chartLabels' => $groupedChartData->first()['labels'] ?? [],
            'chartData' => $groupedChartData->first()['data'] ?? [],
            'groupedChartData' => $groupedChartData,
            'totalQty' => $totalQty,
        ]);
    }
}
