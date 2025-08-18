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

        $query = DiInputModel::query();

        if ($tanggal) {
            $query->where('di_received_date_string', $tanggal);
        }

        // Filter supplier part number (hapus spasi untuk pencocokan lebih fleksibel)
        if ($supplierPartNumber) {
            $query->whereRaw(
                "REPLACE(supplier_part_number, ' ', '') LIKE ?",
                ['%' . str_replace(' ', '', $supplierPartNumber) . '%']
            );
        }

        $query->orderByDesc('di_received_date_string');

        // Cek apakah ada filter
        $isFiltered = $tanggal || $supplierPartNumber;

        // Kalau ada filter → paginate(10), kalau tidak → ambil 5 data (tanpa pagination)
        if ($isFiltered) {
            $timeline = $query->paginate(10)->appends($request->all());
        } else {
            $timeline = $query->limit(5)->get(); // tanpa pagination
        }

        $totalQty = $timeline->sum('qty');

        // Data untuk chart (group berdasarkan tanggal di DB, format d-m-Y)
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

        // ==========================
        // Data status preparation 7 hari terakhir
        // ==========================
        
        // Hitung tanggal 7 hari yang lalu dalam format string yang sesuai dengan database
        $sevenDaysAgo = Carbon::now()->subDays(7)->format('Y-m-d');
        
        // Hitung completed (berdasarkan di_status = 'Completed')
        $completed = DsInput::where('di_received_date_string', '>=', $sevenDaysAgo)
            ->where('di_status', 'Completed')
            ->count();

        // Hitung non-completed (semua selain 'Completed')
        $nonCompleted = DsInput::where('di_received_date_string', '>=', $sevenDaysAgo)
            ->where(function($query) {
                $query->where('di_status', '!=', 'Completed')
                      ->orWhereNull('flag');
            })
            ->count();

        // Format data untuk chart status
        $statusData = [
            'completed' => $completed,
            'non_completed' => $nonCompleted,
        ];

        return view('dashboard', [
            'timeline' => $timeline,
            'chartLabels' => $groupedChartData->first()['labels'] ?? [],
            'chartData' => $groupedChartData->first()['data'] ?? [],
            'groupedChartData' => $groupedChartData,
            'totalQty' => $totalQty,
            'isFiltered' => $isFiltered,
            'statusData' => $statusData, // sesuaikan nama variabel dengan yang digunakan di view
        ]);
    }
}