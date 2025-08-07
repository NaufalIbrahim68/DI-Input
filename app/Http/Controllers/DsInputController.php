<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

class DsInputController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10); // Default 10 entries per page
        $search = $request->get('search');
        $currentPage = $request->get('page', 1);

        // Start building the query
        $query = DB::table('ds_input');

        // Add search functionality
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('ds_number', 'like', "%{$search}%")
                  ->orWhere('gate', 'like', "%{$search}%")
                  ->orWhere('supplier_part_number', 'like', "%{$search}%")
                  ->orWhere('di_type', 'like', "%{$search}%")
                  ->orWhere('di_status', 'like', "%{$search}%");
            });
        }

        // Get total count for pagination
        $total = $query->count();

        // Add ordering and pagination
        $offset = ($currentPage - 1) * $perPage;
        $items = $query->orderBy('created_at', 'desc')
                      ->offset($offset)
                      ->limit($perPage)
                      ->get()
                      ->map(function ($item) {
                          if (!empty($item->di_received_date_string)) {
                              try {
                                  $carbonDate = Carbon::parse($item->di_received_date_string);
                                  $item->di_received_date_display = $carbonDate->format('d-m-Y'); // tampilan
                                  $item->di_received_date_string = $carbonDate->format('Y-m-d'); // untuk input
                              } catch (\Exception $e) {
                                  $item->di_received_date_display = '-';
                                  $item->di_received_date_string = null;
                              }
                          } else {
                              $item->di_received_date_display = '-';
                              $item->di_received_date_string = null;
                          }
                          return $item;
                      });

        // Create pagination manually
        $dsInputs = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'pageName' => 'page',
            ]
        );

        // Preserve query parameters
        $dsInputs->appends($request->query());
        return view('ds_input.index', compact('dsInputs'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'gate' => 'required|string',
            'supplier_part_number' => 'required|string',
            'qty' => 'required|integer|min:1',
            'di_type' => 'required|string',
            'di_status' => 'required|string',
            'di_received_date_string' => 'nullable|date'
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
                'di_received_date_string' => $request->di_received_date_string 
                    ? Carbon::parse($request->di_received_date_string)->format('Y-m-d')
                    : null,
                'di_received_time' => Carbon::now()->toTimeString(),
                'created_at' => now(),
                'updated_at' => now(),
                'flag' => $request->flag ?? 0,
            ]);

            return redirect()->back()->with('success', '✅ Data berhasil disimpan!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', '❌ Gagal menyimpan data: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $ds_number)
    {
        $request->validate([
            'gate' => 'required|string',
            'supplier_part_number' => 'required|string',
            'qty' => 'required|integer|min:1',
            'di_type' => 'nullable|string',
            'di_status' => 'nullable|string',
            'di_received_date_string' => 'nullable|date',
            'di_received_time' => 'nullable',
            'flag' => 'required|in:0,1'
        ]);

        DB::table('ds_input')->where('ds_number', $ds_number)->update([
            'gate' => $request->gate,
            'supplier_part_number' => $request->supplier_part_number,
            'qty' => intval($request->qty),
            'di_type' => $request->di_type,
            'di_status' => $request->di_status,
            'di_received_date_string' => $request->di_received_date_string
                ? Carbon::parse($request->di_received_date_string)->format('Y-m-d')
                : null,
            'di_received_time' => $request->di_received_time,
            'updated_at' => now(),
            'flag' => $request->flag ?? 0,
        ]);

        return redirect()->route('ds_input.index')->with('success', '✅ Data berhasil diupdate!');
    }

    public function destroy($ds_number)
    {
        DB::table('ds_input')->where('ds_number', $ds_number)->delete();
        return redirect()->route('ds_input.index')->with('success', '🗑️ Data berhasil dihapus');
    }

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
