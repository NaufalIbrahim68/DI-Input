@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="bg-white p-4 shadow rounded">
        <h2 class="mb-4">Masukkan Nomor DN untuk DS: {{ $ds->ds_number }}</h2>

        <form action="{{ route('ds_input.store_dn', $ds->ds_number) }}" method="POST">
            @csrf

            <div class="mb-3">
                <label for="dn_number" class="form-label">Nomor DN</label>
                <input type="text" name="dn_number" id="dn_number"
                       value="{{ old('dn_number', $ds->dn_number) }}"
                       class="form-control @error('dn_number') is-invalid @enderror"
                       {{ $ds->dn_number ? 'readonly' : '' }}>
                @error('dn_number')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            @if(!$ds->dn_number)
                <button type="submit" class="btn btn-success">💾 Simpan</button>
            @else
                <div class="alert alert-info">Nomor DN sudah diisi: {{ $ds->dn_number }}</div>
                <a href="{{ route('ds_input.index') }}" class="btn btn-secondary">⬅ Kembali</a>
            @endif
        </form>
    </div>
</div>
@endsection
