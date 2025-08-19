<?php

namespace App\Http\Controllers;

use App\Models\Dn_Input;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DnController extends Controller
{   
    public function index()
    {
        // Join ds_input dengan dn_input
       $dnData = DB::table('ds_input as ds')
    ->leftJoin('dn_input as d', 'ds.ds_number', '=', 'd.ds_number')
    ->select(
        'ds.ds_number',
        'ds.qty as ds_qty',
        'd.dn_number',
        'd.qty_dn as dn_qty', // gunakan qty_dn bukan qty
        'd.created_at'
    )
    ->get();

        return view('dn.index', compact('dnData'));
    }

    public function create($ds_number)
    {
        $ds = DB::table('ds_input')->where('ds_number', $ds_number)->first();

        if (!$ds) {
            return redirect()->route('dn.index')->with('error', 'DS tidak ditemukan');
        }

        return view('dn.create', compact('ds'));
    }

   public function store(Request $request, $ds_number)
{
    $request->validate([
        'dn_number' => 'required|string',
        'qty_dn'    => 'required|integer|min:1',
    ]);

    // Cari DS terkait
    $ds = \App\Models\DsInput::where('ds_number', $ds_number)->firstOrFail();

    // Simpan data DN
    Dn_Input::create([
        'ds_number' => $ds->ds_number,
        'dn_number' => $request->dn_number,
        'qty'       => $ds->qty,        // ambil qty dari DS
        'qty_dn'    => $request->qty_dn // ambil qty dari form input
    ]);

    return redirect()->route('dn.index')
                     ->with('success', 'DN berhasil ditambahkan');
}

}
