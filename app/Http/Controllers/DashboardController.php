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

        // Coba parsing tanggal kalau ada input
        if ($request->filled('tanggal')) {
            try {
                $tanggal = Carbon::parse($request->input('tanggal'))->format('Y-m-d');
            } catch (\Exception $e) {
                return back()->withErrors(['tanggal' => 'Format tanggal tidak valid']);
            }
        }

        // Query dasar
        $query = DiInputModel::query();

        // Filter tanggal (jika ada)
        if ($tanggal) {
            $query->whereDate('di_received_date', $tanggal);
        }

        // Filter supplier_part_number
        if ($supplierPartNumber) {
            $query->whereRaw("REPLACE(supplier_part_number, ' ', '') LIKE ?", ['%' . str_replace(' ', '', $supplierPartNumber) . '%']);
        }

        // Urutkan berdasarkan tanggal
        $query->orderByDesc('di_received_date');

        // Ambil data sesuai kondisi
        if (!$request->filled('tanggal') && !$request->filled('supplier_part_number')) {
            $timeline = $query->take(5)->get();
        } else {
            $timeline = $query->get();
        }

        // Total semua qty dari data yang ditampilkan di timeline
        $totalQty = $timeline->sum('qty');

        // Logging
        logger('📅 Tanggal filter: ' . ($tanggal ?? 'tidak diisi'));
        logger('🔍 Supplier PN filter: ' . ($supplierPartNumber ?? 'tidak diisi'));
        logger('📊 Hasil count: ' . $timeline->count());
        logger('📦 Total Qty: ' . $totalQty);

        // Data untuk chart
        $chartData = DiInputModel::selectRaw("
            CONVERT(VARCHAR, di_received_date, 23) as tanggal,
            SUM(ISNULL(qty, 0)) as total_qty
        ")
            ->groupBy('di_received_date')
            ->orderBy('tanggal')
            ->get();

        return view('dashboard', [
            'timeline' => $timeline,
            'chartLabels' => $chartData->pluck('tanggal')->toArray(),
            'chartData' => $chartData->pluck('total_qty')->toArray(),
            'totalQty' => $totalQty, 
        ]);
    }
}
