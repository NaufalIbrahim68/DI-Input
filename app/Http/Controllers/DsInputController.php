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
    $selectedDate = $request->input('tanggal');

    $query = DsInput::query();

    if ($selectedDate) {
        $query->whereDate('di_received_date_string', $selectedDate);
    }

    // Hitung total sebelum paginate
    $total = $query->count();

    // Baru lakukan paginate (query clone biar tidak "terpotong")
    $dsInputs = $query->orderBy('ds_number')
        ->paginate(10)
        ->withQueryString(); // ⬅ biar ?tanggal tetap ada di link pagination

    return view('ds_input.index', compact('dsInputs', 'selectedDate', 'total'));
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

    // Validasi hanya untuk qty_agv dan dn_number
    $request->validate([
        'qty_agv' => 'nullable|integer|min:0',
        'dn_number' => 'nullable|string|max:50',
    ]);

    // Update kedua kolom ini
    $ds->update([
        'qty_agv'  => $request->qty_agv ?: null,
        'dn_number' => $request->dn_number ?: null,
    ]);

    // Update flag_agv otomatis
    $ds->flag_agv = ($ds->qty_agv == $ds->qty && $ds->qty > 0) ? 1 : 0;
    $ds->save();

    return redirect()
        ->route('ds_input.index', $request->only(['tanggal','page']))
        ->with('success', "Data DS {$ds_number} berhasil diperbarui.");
}



    // ✅ Hapus DS
public function destroy($ds_number, Request $request)
{
    $request->validate([
        'password' => 'required',
        'reason'   => 'required|min:5'
    ]);

    $specialPassword = env('DS_DELETE_PASSWORD');

    if ($request->password !== $specialPassword) {
        return redirect()->back()
            ->withInput()
            ->with('error', 'Password yang anda masukkan salah');
    }

    try {
        DB::beginTransaction();

        // Cari data DS berdasarkan ds_number
        $ds = DB::table('ds_input')->where('ds_number', $ds_number)->first();

        if (!$ds) {
            DB::rollBack();
            return redirect()->back()->with('error', 'DS tidak ditemukan');
        }

        // Log attempt
        Log::info('DS Delete Attempt', [
            'ds_number' => $ds->ds_number,
            'deleted_by' => Auth::user()->name ?? 'Unknown',
            'user_id'   => Auth::id(),
            'reason'    => $request->reason,
            'timestamp' => now(),
            'ip_address'=> $request->ip()
        ]);

        // Hapus dari ds_input
        DB::table('ds_input')->where('ds_number', $ds_number)->delete();

        // Hapus juga di di_input berdasarkan relasi di_id
        if ($ds->di_id) {
            DB::table('di_input')->where('id', $ds->di_id)->delete();
        }

        DB::commit();

        return redirect()->back()->with('success', "DS {$ds->ds_number} berhasil dihapus");

    } catch (\Exception $e) {
        DB::rollBack();
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
    } else {
        // kalau tidak ada input, pakai tanggal hari ini
        $tanggal = null;
    }

    $dsInputs = $query->orderBy('ds_number')->get();

    $pdf = Pdf::loadView('Ds_Input.pdf', [
            'dsInputs' => $dsInputs,
            'tanggal'  => $tanggal,
        ])
        ->setPaper('a4', 'landscape');

    // bikin nama file sesuai kondisi tanggal
    if ($tanggal) {
        $filename = 'ds_input_' . Carbon::parse($tanggal)->format('d-m-Y') . '.pdf';
    } else {
        $filename = 'ds_input.pdf';
    }

    return $pdf->download($filename);
}



// Export Excel
public function exportExcel(Request $request)
{
    $tanggal = $request->input('tanggal'); // ambil dari query string

    // Tentukan nama file
    $filename = $tanggal
        ? 'ds_input_' . Carbon::parse($tanggal)->format('d-m-Y') . '.xlsx'
        : 'ds_input.xlsx';

    return Excel::download(new DsInputExport($tanggal), $filename);
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

        // Gabungkan DI yang sama berdasarkan kombinasi unik
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
            $first->qty = $group->sum('qty');
            $first->di_ids = $group->pluck('id')->toArray(); // simpan semua id DI yg tergabung
            return $first;
        });

        // Urutkan berdasarkan gate alfabetis
        $sorted = $merged->sortBy('gate')->values();

        // Ambil DS terakhir untuk tanggal tersebut
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

            // Simpan ke tabel DS (relasi ke DI via di_id)
            $ds = DsInput::create([
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
                'di_id'                   => $di->id, // ambil id DI yg pertama
            ]);

            // Kalau kamu mau semua DI id yang tergabung, bisa bikin tabel pivot ds_di_relations
            // foreach ($di->di_ids as $diId) {
            //     DsDiRelation::create([
            //         'ds_id' => $ds->id,
            //         'di_id' => $diId,
            //     ]);
            // }
        }

        DB::commit();

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