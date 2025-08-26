@extends('layouts.app')

@section('content')
<div class="container">

    {{-- Notifikasi --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- Form Edit --}}
    <form action="{{ route('deliveries.update', $delivery->di_no) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label class="form-label text-black">DI No</label>
            <input type="text" name="di_no" class="form-control text-dark" 
                   value="{{ old('di_no', $delivery->di_no) }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label text-black">Gate</label>
            <input type="text" name="gate" class="form-control text-dark" 
                   value="{{ old('gate', $delivery->gate) }}">
        </div>

        <div class="mb-3">
            <label class="form-label text-black">PO Number</label>
            <input type="text" name="po_number" class="form-control text-dark" 
                   value="{{ old('po_number', $delivery->po_number) }}">
        </div>

        <div class="mb-3">
            <label class="form-label text-black">Supplier Part Number</label>
            <input type="text" name="supplier_part_number" class="form-control text-dark" 
                   value="{{ old('supplier_part_number', $delivery->supplier_part_number) }}">
        </div>

        <div class="mb-3">
            <label class="form-label text-black">BAAN PN</label>
            <input type="text" class="form-control text-dark" 
                   value="{{ $delivery->baan_pn }}" readonly>
        </div>

        <div class="mb-3">
            <label class="form-label text-black">Visteon PN</label>
            <input type="text" class="form-control text-dark" 
                   value="{{ $delivery->visteon_pn }}" readonly>
        </div>

        <div class="mb-3">
            <label class="form-label text-black">Qty</label>
            <input type="number" name="qty" class="form-control text-dark" 
                   value="{{ old('qty', $delivery->qty) }}">
        </div>

        <div class="mb-3">
            <label class="form-label text-black">DI Type</label>
            <input type="text" name="di_type" class="form-control text-dark" 
                   value="{{ old('di_type', $delivery->di_type) }}">
        </div>

        <div class="mb-3">
            <label class="form-label text-black">DI Received Date</label>
            <input type="date" name="di_received_date_string" 
                   class="form-control text-dark" 
                   value="{{ $delivery->di_received_date_string 
                            ? \Carbon\Carbon::parse($delivery->di_received_date_string)->format('Y-m-d') 
                            : '' }}">
        </div>

        <div class="mb-3">
            <label class="form-label text-black">DI Received Time</label>
            <input type="time" name="di_received_time" 
                   class="form-control text-dark" 
                   value="{{ $delivery->di_received_time 
                            ? \Carbon\Carbon::parse($delivery->di_received_time)->format('H:i') 
                            : '' }}">
        </div>

          {{-- Tombol aksi dalam satu baris --}}
        <div class="d-flex gap-2 mt-3">
            <button type="submit" class="btn btn-primary">Update</button>
            
            <button type="button" class="btn btn-danger" 
                    onclick="if(confirm('Yakin ingin menghapus data ini?')) {
                        document.getElementById('deleteForm').submit();
                    }">
                Delete
            </button>
            
            <a href="{{ route('deliveries.index') }}" class="btn btn-secondary">Back</a>
        </div>
    </form>

    {{-- Hidden Delete Form --}}
    <form id="deleteForm" action="{{ route('deliveries.destroy', $delivery->di_no) }}" 
          method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>

</div>
@endsection