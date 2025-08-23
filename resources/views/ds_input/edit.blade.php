@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="row justify-content-center">
    <div class="col-lg-10">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-light">
          <h5 class="mb-0">Edit Delivery Schedule (DS)</h5>
        </div>

        <form method="POST" action="{{ route('ds_input.update', $ds->ds_number) }}" class="card-body">
          @csrf
          @method('PUT')

          {{-- tampilkan error validasi --}}
          @if ($errors->any())
            <div class="alert alert-danger">
              <strong>Periksa kembali input:</strong>
              <ul class="mb-0">
                @foreach ($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          <div class="row g-3">
            {{-- DS Number (readonly) --}}
            <div class="col-md-6">
              <label class="form-label">DS Number</label>
              <input type="text" class="form-control" value="{{ $ds->ds_number }}" readonly>
            </div>

            {{-- Gate --}}
            <div class="col-md-6">
              <label class="form-label">Gate</label>
              <input type="text" name="gate" class="form-control"
                     value="{{ old('gate', $ds->gate) }}" required>
            </div>

            {{-- Supplier Part Number --}}
            <div class="col-md-8">
              <label class="form-label">Supplier Part Number</label>
              <input type="text" name="supplier_part_number" class="form-control"
                     value="{{ old('supplier_part_number', $ds->supplier_part_number) }}" required>
            </div>

            {{-- Qty --}}
            <div class="col-md-4">
              <label class="form-label">Qty</label>
              <input type="number" name="qty" min="0" step="1" class="form-control"
                     value="{{ old('qty', $ds->qty) }}" required>
            </div>

            {{-- DI Type --}}
            <div class="col-md-6">
              <label class="form-label">DI Type</label>
              <input type="text" name="di_type" class="form-control"
                     value="{{ old('di_type', $ds->di_type ?? '') }}">
            </div>

            {{-- DI Status --}}
            <div class="col-md-6">
              <label class="form-label">DI Status</label>
              <input type="text" name="di_status" class="form-control"
                     value="{{ old('di_status', $ds->di_status ?? '') }}">
            </div>

            {{-- DI Received Date --}}
            <div class="col-md-6">
              <label class="form-label">DI Received Date</label>
              <input type="date" name="di_received_date" class="form-control"
                     value="{{ old('di_received_date', isset($ds->di_received_date) ? \Carbon\Carbon::parse($ds->di_received_date)->format('Y-m-d') : '') }}">
            </div>

            {{-- DI Received Time --}}
            <div class="col-md-6">
              <label class="form-label">DI Received Time</label>
              <input type="time" name="di_received_time" class="form-control"
                     value="{{ old('di_received_time', $ds->di_received_time ?? '') }}">
            </div>
          </div>

          <div class="d-flex justify-content-between mt-4">
            <a href="{{ route('ds_input.index') }}" class="btn btn-outline-secondary">Kembali</a>
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
