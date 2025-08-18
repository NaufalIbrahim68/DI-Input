<?php

namespace App\Http\Controllers;

use App\Models\Dn_Input;
use App\Models\DnInput;
use App\Models\DsInput;
use Illuminate\Http\Request;

class DnController extends Controller
{
    public function index()
    {
        $dnList = Dn_Input::with('ds')->get();
        return view('dn.index', compact('dnList'));
    }

    public function create($ds_number)
    {
        $ds = DsInput::where('ds_number', $ds_number)->firstOrFail();
        return view('dn.create', compact('ds'));
    }

    public function store(Request $request, $ds_number)
    {
        $request->validate([
            'dn_number' => 'required|string|max:50',
            'qty' => 'required|integer|min:1'
        ]);

        Dn_Input::create([
            'ds_number' => $ds_number,
            'dn_number' => $request->dn_number,
            'qty'       => $request->qty,
        ]);

        return redirect()->route('dn.index')->with('success', 'DN berhasil ditambahkan!');
    }
}
