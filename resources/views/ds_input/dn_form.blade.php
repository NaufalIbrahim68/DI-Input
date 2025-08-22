@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="bg-white p-4 shadow rounded">
        <h2 class="mb-4 text-black">Masukkan Nomor DN untuk DS: {{ $ds->ds_number }}</h2>

        <form action="{{ route('dn.store', $ds->ds_number) }}" method="POST">
            @csrf

          {{-- Nomor DN --}}
<div class="mb-3">
    <label for="dn_number" class="form-label text-black">Nomor DN</label>
    <input type="text" name="dn_number" id="dn_number" 
           class="form-control" value="{{ old('dn_number') }}">
</div>

{{-- Quantity DN (input user) --}}
<div class="mb-3">
    <label for="qty_dn" class="form-label text-black">Quantity DN</label>
    <input type="number" name="qty_dn" id="qty_dn" 
           class="form-control" value="{{ old('qty_dn') }}" min="1">
</div>
            <button type="submit" class="btn btn-success">💾 Simpan</button>
            @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
            <a href="{{ route('ds_input.index') }}" class="btn btn-secondary">⬅ Kembali</a>
        </form>
    </div>
</div>
@endsection
