<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DsInput;
use App\Models\DiInputModel;
use App\Imports\DsInputImport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB; 
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\DsInputExport; 
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class DsInputController extends Controller
{
    // ✅ Halaman Data DS
   
public function index(Request $request)
{
    $query = DsInput::query();

    // filter tanggal kalau ada
    if ($request->filled('tanggal')) {
        // pastikan format tanggal sama dengan kolom di DB
        $query->whereDate('di_received_date_string', $request->tanggal);
    }

    // urutkan berdasarkan ds_number atau created_at
    $dsInputs = $query->orderBy('ds_number')->get();

    return view('ds_input.index', compact('dsInputs'));
}
    // ✅ Import DS dari Excel
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls'
        ]);

        $import = new DsInputImport();
        Excel::import($import, $request->file('file'));

        $successCount = $import->getSuccessCount();
        $totalRows = count(Excel::toArray(new DsInputImport(), $request->file('file'))[0]);
        $failedCount = $totalRows - $successCount;

        return back()->with('success', "Import selesai: {$successCount} data berhasil, {$failedCount} gagal.");
    }

    // ✅ Edit DS
   public function edit($ds_number)
{
    $ds = DsInput::where('ds_number', $ds_number)->firstOrFail();
    return view('Ds_Input.edit', compact('ds'));
}
    // ✅ Update DS
   public function update(Request $request, $ds_number)
{
    $ds = DsInput::where('ds_number', $ds_number)->firstOrFail();

    // Validasi hanya untuk qty_delivery dan dn_number
    $request->validate([
        'qty_delivery' => 'nullable|integer|min:0',
        'dn_number' => 'nullable|string|max:50',
    ]);

    // Update hanya kedua kolom ini
    $ds->update([
        'qty_delivery' => $request->qty_delivery ?: null,
        'dn_number' => $request->dn_number ?: null,
    ]);

    return redirect()
        ->route('ds_input.index', $request->only(['tanggal','page']))
        ->with('success', "Data DS {$ds_number} berhasil diperbarui.");
}


    // ✅ Hapus DS
  public function destroy($ds_number, Request $request)
{
    // 1. Validasi input terlebih dahulu
    $request->validate([
        'password' => 'required',
        'reason' => 'required|min:5'
    ], [
        'password.required' => 'Password wajib diisi',
        'reason.required' => 'Alasan hapus wajib diisi',
        'reason.min' => 'Alasan hapus minimal 5 karakter'
    ]);
    
    $specialPassword = env('DS_DELETE_PASSWORD');

     if ($request->password !== $specialPassword) {
        return redirect()->back()
            ->withInput()
            ->with('error', 'Password yang anda masukkan salah');
    }
    
    try {
        // 3. Cek apakah DS exists terlebih dahulu
        $dsExists = DB::table('ds_input')
            ->where('ds_number', $ds_number)
            ->exists();
            
        if (!$dsExists) {
            return redirect()->back()->with('error', 'DS tidak ditemukan');
        }
        
        // 4. Log aktivitas penghapusan sebelum delete (untuk audit trail)
        Log::info('DS Delete Attempt', [
            'ds_number' => $ds_number,
            'deleted_by' => Auth::user()->name ?? 'Unknown',
            'user_id' => Auth::id(),
            'reason' => $request->reason,
            'timestamp' => now(),
            'ip_address' => $request->ip()
        ]);
        
        // 5. Lakukan penghapusan
        $deleted = DB::table('ds_input')
            ->where('ds_number', $ds_number)
            ->delete();
            
        if ($deleted) {
            // Log sukses
            Log::info('DS Successfully Deleted', [
                'ds_number' => $ds_number,
                'deleted_by' => Auth::user()->name ?? 'Unknown',
                'reason' => $request->reason
            ]);
            
            return redirect()->back()->with('success', 'DS ' . $ds_number . ' berhasil dihapus');
        } else {
            return redirect()->back()->with('error', 'Gagal menghapus DS');
        }
        
    } catch (\Exception $e) {
        // Log error untuk debugging
        Log::error('DS Delete Error', [
            'ds_number' => $ds_number,
            'error' => $e->getMessage(),
            'user_id' => Auth::id(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return redirect()->back()->with('error', 'Gagal menghapus DS: ' . $e->getMessage());
    }
}

    // ✅ Export PDF
   public function exportPdf(Request $request)
{
    $tanggal = $request->input('tanggal'); // ambil dari query string ?tanggal=YYYY-MM-DD

    $query = DsInput::query();

    if ($tanggal) {
        $query->whereDate('di_received_date_string', $tanggal);
    }

    $dsInputs = $query->orderBy('ds_number')->get();

    $pdf = Pdf::loadView('Ds_Input.pdf', compact('dsInputs'))
        ->setPaper('a4', 'landscape');

    return $pdf->download('ds_input.pdf');
}

// Export Excel
public function exportExcel(Request $request)
{
    $tanggal = $request->input('tanggal'); // ambil dari query string

    return Excel::download(new DsInputExport($tanggal), 'ds_input.xlsx');
}
public function generate(Request $request)
{
    // Validasi input tanggal
    $request->validate([
        'generate_date' => 'required|date',
    ]);

    $date = $request->generate_date;

    try {
        DB::beginTransaction();

        // Ambil semua DI (Delivery Instruction) untuk tanggal tersebut
        $diData = DiInputModel::whereDate('di_received_date_string', $date)->get();

        if ($diData->isEmpty()) {
            return redirect()
                ->route('ds_input.generatePage', ['generate_date' => $date])
                ->with('error', 'Tidak ada data DI pada tanggal tersebut.');
        }

        // Gabungkan DI yang sama berdasarkan kombinasi unik: gate, di_type, supplier_part_number, received date & time
        $grouped = $diData->groupBy(function ($item) {
            return $item->gate . '|' .
                   $item->di_type . '|' .
                   $item->supplier_part_number . '|' .
                   $item->di_received_date_string . '|' .
                   $item->di_received_time;
        });

        // Merge duplikat dengan menjumlahkan qty
        $merged = $grouped->map(function ($group) {
            $first = $group->first();
            $first->qty = $group->sum('qty'); // jumlahkan qty jika ada duplikat
            return $first;
        });

        // Urutkan berdasarkan gate alfabetis
        $sorted = $merged->sortBy('gate')->values();

        // Ambil DS terakhir untuk tanggal tersebut, untuk generate nomor berikutnya
        $prefix = Carbon::parse($date)->format('dmy');
        $last = DsInput::where('ds_number', 'like', "DS-$prefix-%")
            ->orderBy('ds_number', 'desc')
            ->first();

        $counter = $last ? intval(substr($last->ds_number, -4)) + 1 : 1;

        foreach ($sorted as $di) {
            // Skip insert jika kombinasi DS sudah ada
            if (DsInput::where('gate', $di->gate)
                ->where('di_type', $di->di_type)
                ->where('supplier_part_number', $di->supplier_part_number)
                ->where('di_received_date_string', $di->di_received_date_string)
                ->where('di_received_time', $di->di_received_time)
                ->exists()
            ) continue;

            // Buat nomor DS baru
            $dsNumber = "DS-$prefix-" . str_pad($counter++, 4, '0', STR_PAD_LEFT);

            // Simpan ke tabel DS
            DsInput::create([
                'ds_number'               => $dsNumber,
                'gate'                    => $di->gate ?? '-',
                'supplier_part_number'    => $di->supplier_part_number,
                'qty'                     => $di->qty,
                'di_type'                 => $di->di_type ?? null,
                'di_status'               => $di->di_status ?? null,
                'di_received_date_string' => $di->di_received_date_string ?? $date,
                'di_received_time'        => $di->di_received_time ?? null,
                'flag_prep'               => $di->flag_prep ?? 0,
                'flag_record'             => $di->flag_record ?? 0,
            ]);
        }

        DB::commit();

        // Redirect dengan pesan sukses
        return redirect()
            ->route('ds_input.generatePage', ['generate_date' => $date])
            ->with('success', 'Generate DS selesai.');

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Generate DS gagal: '.$e->getMessage());

        return redirect()
            ->route('ds_input.generatePage', ['generate_date' => $date])
            ->with('error', 'Terjadi kesalahan saat generate DS. Silakan cek log.');
    }
}

public function generateForm(Request $request)
{
    $dsInputs = collect();

    if ($request->filled('generate_date')) {
        $dsInputs = DsInput::whereDate('di_received_date_string', $request->generate_date)
                            ->orderBy('ds_number')
                            ->get();
    }

    return view('ds_input.generate_ds', [
        'dsInputs' => $dsInputs,
        'generateDate' => $request->generate_date ?? null
    ]);
}

}